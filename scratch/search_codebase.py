import os
import re

search_dir = r"c:\Users\Metehan\Documents\GitHub\-sg_Elektrik_Periyodik_Muayene_Otomasyonu\htdocs"
keywords = ["112", "söndürme", "sondurme", "sahanlık", "sahanlik", "yangın dolap", "yangin dolap"]

for root, dirs, files in os.walk(search_dir):
    for file in files:
        if file.endswith(".php") or file.endswith(".js") or file.endswith(".html"):
            path = os.path.join(root, file)
            try:
                with open(path, "r", encoding="utf-8", errors="ignore") as f:
                    content = f.read()
                lines = content.split("\n")
                for idx, line in enumerate(lines):
                    for kw in keywords:
                        if kw.lower() in line.lower():
                            print(f"File: {path} (Line {idx+1}):")
                            print(f"  {line.strip()}")
                            print("-" * 40)
            except Exception as e:
                pass
