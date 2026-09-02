import hashlib
import logging
import time
from collections import OrderedDict

import requests as http_requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

from vector_db import load_vector_db, similarity_search

logger = logging.getLogger(__name__)

OLLAMA_URL  = "http://localhost:11434/api/generate"
LLM_MODEL   = "phi3:mini"
CACHE_MAX   = 200 
PROMPT_TEMPLATE = (
    "You are a customer assistant for Almas Clothing. "
    "Answer in 4-6 sentence using ONLY the context. "
    "If unknown say: I don't have that info.\n\n"
    "Context: {context}\n"
    "Q: {question}\n"
    "A:"
)


def _make_session() -> http_requests.Session:
    """Persistent TCP session with retry — avoids re-handshaking Ollama each call."""
    s = http_requests.Session()
    retry = Retry(total=2, backoff_factor=0.2, status_forcelist=[500, 502, 503])
    adapter = HTTPAdapter(max_retries=retry, pool_connections=1, pool_maxsize=2)
    s.mount("http://", adapter)
    return s


class ChatbotEngine:
    def __init__(self):
        self.vectordb  = None
        self._cache: OrderedDict[str, str] = OrderedDict()
        # ⚡ FIX 4 — Reuse one HTTP session for all Ollama calls
        self._session  = _make_session()

    def initialize(self):
        """Load vector DB once at startup. Blocks intentionally — runs before first request."""
        self.vectordb = load_vector_db()
        logger.info("ChatbotEngine initialized ✅")

    # ── Cache helpers ──────────────────────────────────────────────────────
    def _cache_key(self, question: str) -> str:
        return hashlib.md5(question.lower().strip().encode()).hexdigest()

    def _cache_get(self, key: str):
        if key in self._cache:
            self._cache.move_to_end(key)   # LRU — keep recently used alive
            return self._cache[key]
        return None

    def _cache_set(self, key: str, value: str):
        if len(self._cache) >= CACHE_MAX:
            self._cache.popitem(last=False)  # evict oldest
        self._cache[key] = value

    # ── Ollama call ────────────────────────────────────────────────────────
    def _call_ollama(self, prompt: str) -> str:
        payload = {
            "model":  LLM_MODEL,
            "prompt": prompt,
            "stream": False,
            "keep_alive": "10m",   
            "options": {
                # ⚡ FIX 6 — num_ctx=256 (was 512). Prompt + answer fit easily.
                #   Halving context window roughly halves attention cost on CPU.
                "num_ctx":        256,
                # ⚡ FIX 7 — num_predict=60 (was 100). 1 sentence ≈ 25–40 tokens.
                #   Fewer max tokens = model stops sooner = faster wall-clock time.
                "num_predict":    60,
                "temperature":    0.0,   # fully deterministic = no sampling overhead
                "top_k":          1,     # ⚡ FIX 8 — top_k=1 disables nucleus sampling
                "top_p":          1.0,
                "repeat_penalty": 1.0,
                # ⚡ FIX 9 — stop tokens make the model halt immediately after answer
                "stop": ["\n", "Q:", "Context:"],
            },
        }

        try:
            t0 = time.time()
            resp = self._session.post(OLLAMA_URL, json=payload, timeout=25)
            resp.raise_for_status()
            data    = resp.json()
            answer  = data.get("response", "").strip()
            logger.info(f"  Ollama: {(time.time()-t0)*1000:.0f}ms | tokens_out={data.get('eval_count','?')}")
            return answer
        except http_requests.exceptions.Timeout:
            logger.error("Ollama timeout (25s)")
            return "I'm taking too long to respond. Please try again."
        except Exception as e:
            logger.error(f"Ollama error: {e}")
            return "Sorry, I'm having trouble connecting right now."

    # ── Public API ─────────────────────────────────────────────────────────
    def ask(self, question: str) -> str:
        key = self._cache_key(question)

        # 1. Cache hit → instant return
        cached = self._cache_get(key)
        if cached:
            logger.info(f"Cache HIT: {question[:50]}")
            return cached

        t_start = time.time()

        # 2. Vector search — k=1 for max speed (one chunk is enough for short answers)
        t1 = time.time()
        context = similarity_search(self.vectordb, question, k=1)
        logger.info(f"  Retrieval: {(time.time()-t1)*1000:.0f}ms")

        # 3. Build prompt — hard char limits keep token count predictable
        prompt = PROMPT_TEMPLATE.format(
            context=context[:400],   # ⚡ FIX 10 — 400 chars (was 800) ≈ ~100 tokens
            question=question[:150],
        )

        # 4. LLM call
        answer = self._call_ollama(prompt)

        logger.info(f"Total ask(): {(time.time()-t_start)*1000:.0f}ms")

        # 5. Cache & return
        self._cache_set(key, answer)
        return answer
