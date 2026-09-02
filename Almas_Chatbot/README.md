# 🧥 Almas Clothing — AI Chatbot System

A **fast RAG-based chatbot** for Almas Clothing built with FastAPI + ChromaDB + Ollama (phi3:mini).
Target response time: **≤ 5 seconds**.

---

## 📁 Project Structure

```
almas_chatbot/
├── api.py              ← FastAPI server (start this to run the bot)
├── build_index.py      ← Run ONCE to build the knowledge base
├── scraper.py          ← Scrapes public website pages
├── db_loader.py        ← Loads products from MySQL
├── vector_db.py        ← ChromaDB builder & loader
├── chatbot_logic.py    ← RAG pipeline + caching
├── requirements.txt    ← Python dependencies
├── chroma_db/          ← Auto-created after build_index.py
└── frontend/
    ├── chatbot.html    ← Standalone demo page
    ├── chatbot.css     ← Widget styles
    ├── chatbot.js      ← Widget logic & API calls
    └── chatbot_embed.php ← Drop into any PHP page
```

---

## ⚙️ Prerequisites

### 1. Install Ollama
Download from: https://ollama.ai

Then pull required models (run in CMD):
```bash
ollama pull phi3:mini
ollama pull nomic-embed-text
```

Verify Ollama is running:
```bash
ollama serve
```

### 2. Python 3.10+
Download from: https://python.org

### 3. XAMPP Running
- Apache + MySQL must be running
- Your Almas Clothing website must be accessible at `http://localhost/Clothing Brand/`

---

## 🚀 Setup (Step by Step)

### Step 1 — Install Python dependencies

Open CMD in the `almas_chatbot/` folder:
```bash
pip install -r requirements.txt
```

### Step 2 — Configure MySQL connection

Edit `db_loader.py` and update these values:
```python
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",          # Your MySQL password (empty by default in XAMPP)
    "database": "almas_db",  # YOUR actual database name
    "port": 3306,
}
```

> **How to find your DB name:** Open phpMyAdmin → look for the database containing the `products` table.

### Step 3 — Build the knowledge base (Run ONCE)

```bash
python build_index.py
```

This will:
- Scrape all public website pages
- Load all products from MySQL
- Generate embeddings using nomic-embed-text
- Save to `chroma_db/` folder

**Expected time:** 2–5 minutes (only needed once)

### Step 4 — Start the API server

```bash
python api.py
```

You should see:
```
✅ Chatbot Engine Ready!
INFO:     Uvicorn running on http://0.0.0.0:8000
```

**Test it:**
```bash
curl -X POST http://localhost:8000/chat \
  -H "Content-Type: application/json" \
  -d "{\"question\": \"What is your return policy?\"}"
```

### Step 5 — Add chatbot to website

**Option A — Embed in all PHP pages (recommended)**

Copy `frontend/` folder to:
```
C:/xampp/htdocs/Clothing Brand/chatbot/frontend/
```

Add this ONE line before `</body>` in every PHP page (index.php, shop.php, etc.):
```php
<?php include('chatbot/frontend/chatbot_embed.php'); ?>
```

**Option B — Standalone test**

Open `frontend/chatbot.html` in a browser while api.py is running.

---

## 🔁 Daily Usage

After the first setup, you only need to:

1. Start Ollama: `ollama serve` (or it starts automatically)
2. Start API: `python api.py`
3. Your XAMPP website will automatically have the chatbot

---

## 🔄 Rebuilding the Knowledge Base

Only rebuild when you add/update products or change website content:
```bash
python build_index.py
```

Then restart `api.py`.

---

## ⚡ Performance Tuning

If responses are still slow (>5 sec), try these in `chatbot_logic.py`:

| Setting | Current | Faster Option |
|---------|---------|---------------|
| `num_ctx` | 512 | 256 |
| `num_predict` | 100 | 60 |
| `k` in similarity_search | 2 | 1 |
| Context char limit | 800 | 500 |

---

## 🛠️ Troubleshooting

| Problem | Fix |
|---------|-----|
| "Ollama error" | Run `ollama serve` in a separate CMD window |
| "MySQL failed" | Check DB_CONFIG in db_loader.py |
| "ChromaDB not found" | Run `python build_index.py` first |
| Slow responses (>10s) | Reduce num_ctx to 256, k to 1 |
| CORS error in browser | Check api.py `allow_origins` setting |
| PHP not loading CSS/JS | Check `$chatbot_base_path` in chatbot_embed.php |

---

## 🔐 Security Notes

- Chatbot only reads: `products` table (name, description, price, stock)
- Does NOT access: orders, users, payments, admin panel
- All data stays local — nothing sent to external servers
- Ollama runs 100% offline on your machine

---

## 📞 Quick Test Questions

After setup, test these:
- "What is your return policy?"
- "Do you deliver in Pakistan?"
- "What size fits 32 waist?"
- "Tell me about your Sherwani collection"
- "What is the price of the formal shirt?"
