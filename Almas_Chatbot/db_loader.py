"""
db_loader.py - Load product data from MySQL and convert to text chunks
ONLY reads products table. No orders, users, or sensitive data.
"""

import mysql.connector
import logging

logger = logging.getLogger(__name__)

# ✅ Update these with your XAMPP MySQL credentials
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",           # Default XAMPP password is empty
    "database": "clothingbrand_db",   # Change to your actual DB name
    "port": 3306,
}

CATEGORY_MAP = {
    1: "Party Wear",
    2: "Wedding Sherwani",
    3: "Formal Wear",
    4: "Eastern Wear",
}


def load_products() -> list[dict]:
    """
    Load all products from MySQL and return as list of text documents.
    Safe: Only reads the products table (name, description, price, stock, category).
    """
    docs = []
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        cursor.execute("""
            SELECT product_id, name, description, category_id, price, stock
            FROM products
            WHERE stock > 0
            ORDER BY product_id
        """)

        rows = cursor.fetchall()
        logger.info(f"Loaded {len(rows)} products from MySQL")

        for row in rows:
            category = CATEGORY_MAP.get(row["category_id"], "General")
            price = f"PKR {float(row['price']):.0f}"
            stock_status = "In Stock" if row["stock"] > 0 else "Out of Stock"
            desc = (row["description"] or "").replace("\r\n", " ").strip()

            # Build human-readable text for this product
            text = (
                f"Product: {row['name']}. "
                f"Category: {category}. "
                f"Price: {price}. "
                f"Availability: {stock_status}. "
                f"Description: {desc}"
            )

            docs.append({
                "source": f"db_product_{row['product_id']}",
                "text": text
            })

        cursor.close()
        conn.close()

    except Exception as e:
        logger.error(f"❌ MySQL connection failed: {e}")
        logger.warning("Continuing without DB data. Check DB_CONFIG in db_loader.py")

    return docs


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    products = load_products()
    print(f"\nLoaded {len(products)} product documents:")
    for p in products:
        print(f"\n  [{p['source']}]\n  {p['text'][:200]}...")
