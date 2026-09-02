"""
Almas Clothing AI Chatbot - FastAPI Backend
OPTIMIZED: async-safe, thread-pool execution, timing logs, warmup endpoint
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn
import logging
import time
from concurrent.futures import ThreadPoolExecutor
import asyncio
from chatbot_logic import ChatbotEngine

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%H:%M:%S",
)
logger = logging.getLogger(__name__)

app = FastAPI(title="Almas Clothing Chatbot API", version="2.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Globals: loaded ONCE at startup, reused for every request ──────────────
chatbot: ChatbotEngine | None = None

_executor = ThreadPoolExecutor(max_workers=2)

@app.on_event("startup")
async def startup_event():
    global chatbot
    logger.info("🚀 Loading Almas Chatbot Engine…")
    chatbot = ChatbotEngine()
    chatbot.initialize()
    logger.info("✅ Engine ready")

    # ✅ Auto-warmup — runs once silently after startup
    loop = asyncio.get_event_loop()
    loop.run_in_executor(_executor, chatbot.ask, "hello")
    logger.info("🔥 Model warmup triggered in background")
@app.on_event("startup")
async def startup_event():
    global chatbot
    t0 = time.time()
    logger.info("🚀 Loading Almas Chatbot Engine…")
    chatbot = ChatbotEngine()
    chatbot.initialize()
    logger.info(f"✅ Engine ready in {time.time()-t0:.1f}s")


# ── Pydantic models ────────────────────────────────────────────────────────
class ChatRequest(BaseModel):
    question: str

class ChatResponse(BaseModel):
    answer: str
    elapsed_ms: int = 0          # visible in response for debugging


# ── Main chat endpoint ─────────────────────────────────────────────────────
@app.post("/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    q = request.question.strip()
    if not q:
        raise HTTPException(status_code=400, detail="Question cannot be empty")
    if chatbot is None:
        raise HTTPException(status_code=503, detail="Chatbot not initialized")

    t0 = time.time()

    # ⚡ FIX 2 — Run blocking I/O (ChromaDB + Ollama HTTP) in thread pool.
    #   This releases the async event loop while waiting, so FastAPI stays responsive.
    loop = asyncio.get_event_loop()
    answer = await loop.run_in_executor(_executor, chatbot.ask, q)

    elapsed_ms = int((time.time() - t0) * 1000)
    logger.info(f"⏱  /chat → {elapsed_ms}ms | Q: {q[:60]}")

    return ChatResponse(answer=answer, elapsed_ms=elapsed_ms)


# ── Warmup: pre-loads model into Ollama's memory with a dummy query ────────
@app.post("/warmup")
async def warmup():
    """Call once after startup to pre-load phi3:mini into RAM."""
    if chatbot is None:
        raise HTTPException(status_code=503, detail="Engine not ready")
    loop = asyncio.get_event_loop()
    await loop.run_in_executor(_executor, chatbot.ask, "hello")
    return {"status": "warmed up"}


@app.get("/health")
async def health():
    return {"status": "ok", "engine": "ready" if chatbot else "not_ready"}


if __name__ == "__main__":
    uvicorn.run("api:app", host="0.0.0.0", port=8000, reload=False, workers=1)
