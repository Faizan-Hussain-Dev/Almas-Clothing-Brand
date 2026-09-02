import logging
logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger(__name__)

from scraper import scrape_all_pages
from db_loader import load_products
from vector_db import build_vector_db

def main():
    logger.info("=" * 60)
    logger.info("🏗️  Almas Clothing - Building Knowledge Base")
    logger.info("=" * 60)

    # Step 1: Scrape website pages
    logger.info("\n📄 Step 1: Scraping website pages...")
    web_docs = scrape_all_pages()
    logger.info(f"   Scraped {len(web_docs)} pages")

    # Step 2: Load MySQL products
    logger.info("\n🗄️  Step 2: Loading products from MySQL...")
    db_docs = load_products()
    logger.info(f"   Loaded {len(db_docs)} product records")

    # Step 3: Merge all data
    all_docs = web_docs + db_docs
    logger.info(f"\n📦 Total documents: {len(all_docs)}")

    if not all_docs:
        logger.error("❌ No documents found! Check scraper and DB connection.")
        return

    # Step 4: Build vector DB
    logger.info("\n🔢 Step 4: Building ChromaDB index (this may take 1–2 min)...")
    vectordb = build_vector_db(all_docs)

    logger.info("\n" + "=" * 60)
    logger.info("✅ Index built successfully!")
    logger.info("👉 Now start the API server: python api.py")
    logger.info("=" * 60)


if __name__ == "__main__":
    main()
