"""
scraper.py - Scrape public-facing pages of Almas Clothing website
ONLY customer-facing pages. No admin, no login required.
"""

import requests
from bs4 import BeautifulSoup
import logging

logger = logging.getLogger(__name__)

# ✅ Only public, customer-facing URLs
PUBLIC_URLS = [
    "http://localhost/Clothing%20Brand/index.php",
    "http://localhost/Clothing%20Brand/shop.php",
    "http://localhost/Clothing%20Brand/size_guide.php",
    "http://localhost/Clothing%20Brand/shipping.php",
    "http://localhost/Clothing%20Brand/return_policy.php",
    "http://localhost/Clothing%20Brand/shop.php?category=1",
    "http://localhost/Clothing%20Brand/shop.php?category=2",
    "http://localhost/Clothing%20Brand/shop.php?category=3",
    "http://localhost/Clothing%20Brand/shop.php?category=4",
]

HEADERS = {
    "User-Agent": "Mozilla/5.0 AlmasBot/1.0"
}

def scrape_page(url: str) -> str:
    """Scrape visible text from a single page."""
    try:
        resp = requests.get(url, headers=HEADERS, timeout=10)
        resp.raise_for_status()
        soup = BeautifulSoup(resp.text, "html.parser")

        # Remove noise elements
        for tag in soup(["script", "style", "noscript", "header", "footer", "nav", "form", "meta"]):
            tag.decompose()

        # Extract meaningful text
        text = soup.get_text(separator=" ", strip=True)

        # Clean up whitespace
        lines = [line.strip() for line in text.splitlines() if len(line.strip()) > 30]
        clean = " ".join(lines)
        return clean

    except Exception as e:
        logger.warning(f"Failed to scrape {url}: {e}")
        return ""


def scrape_all_pages() -> list[dict]:
    """Scrape all public pages and return list of {source, text} dicts."""
    docs = []
    for url in PUBLIC_URLS:
        logger.info(f"Scraping: {url}")
        text = scrape_page(url)
        if text:
            docs.append({"source": url, "text": text})
            logger.info(f"  ✅ Got {len(text)} chars")
        else:
            logger.warning(f"  ⚠️  Empty or failed: {url}")
    return docs


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    results = scrape_all_pages()
    print(f"\nScraped {len(results)} pages successfully.")
    for r in results:
        print(f"  {r['source']}: {len(r['text'])} chars")
