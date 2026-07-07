import sys
import os
import re
import json
from pypdf import PdfReader

def tr_upper(s):
    rep = {
        'i': 'İ', 'ı': 'I', 'ş': 'Ş', 'ğ': 'Ğ', 'ç': 'Ç', 'ü': 'Ü', 'ö': 'Ö'
    }
    for k, v in rep.items():
        s = s.replace(k, v)
    return s.upper()

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file path provided."}))
        return

    pdf_path = sys.argv[1]
    if not os.path.exists(pdf_path):
        print(json.dumps({"error": "File not found."}))
        return

    try:
        reader = PdfReader(pdf_path)
        text = ""
        for page in reader.pages:
            t = page.extract_text()
            if t:
                text += t + "\n"
        
        text_upper = tr_upper(text)
        
        PLATE_CODES = {
            "ADANA": "01", "ADIYAMAN": "02", "AFYONKARAHİSAR": "03", "AFYON": "03", "AĞRI": "04", "AMASYA": "05", "ANKARA": "06", 
            "ANTALYA": "07", "ARTVİN": "08", "AYDIN": "09", "BALIKESİR": "10", "BİLECİK": "11", "BİNGÖL": "12", "BİTLİS": "13", 
            "BOLU": "14", "BURDUR": "15", "BURSA": "16", "ÇANAKKALE": "17", "ÇANKIRI": "18", "ÇORUM": "19", "DENİZLİ": "20", 
            "DİYARBAKIR": "21", "EDİRNE": "22", "ELAZIĞ": "23", "ERZİNCAN": "24", "ERZURUM": "25", "ESKİŞEHİR": "26", 
            "GAZİANTEP": "27", "GİRESUN": "28", "GÜMÜŞHANE": "29", "HAKKARİ": "30", "HATAY": "31", "ISPARTA": "32", 
            "MERSİN": "33", "İÇEL": "33", "İSTANBUL": "34", "İZMİR": "35", "KARS": "36", "KASTAMONU": "37", "KAYSERİ": "38", 
            "KIRKLARELİ": "39", "KIRŞEHİR": "40", "KOCAELİ": "41", "KONYA": "42", "KÜTAHYA": "43", "MALATYA": "44", "MANİSA": "45", 
            "KAHRAMANMARAŞ": "46", "MARAŞ": "46", "MARDİN": "47", "MUĞLA": "48", "MUŞ": "49", "NEVŞEHİR": "50", "NİĞDE": "51", 
            "ORDU": "52", "RİZE": "53", "SAKARYA": "54", "SAMSUN": "55", "SİİRT": "56", "SİNOP": "57", "SİVAS": "58", 
            "TEKİRDAĞ": "59", "TOKAT": "60", "TRABZON": "61", "TUNCELİ": "62", "ŞANLIURFA": "63", "URFA": "63", "UŞAK": "64", 
            "VAN": "65", "YOZGAT": "66", "ZONGULDAK": "67", "AKSARAY": "68", "BAYBURT": "69", "KARAMAN": "70", "KIRIKKALE": "71", 
            "BATMAN": "72", "ŞIRNAK": "73", "BARTIN": "74", "ARDAHAN": "75", "IĞDIR": "76", "YALOVA": "77", "KARABÜK": "78", 
            "KİLİS": "79", "OSMANİYE": "80", "DÜZCE": "81"
        }

        # Date extract
        start_date = None
        date_patterns = [
            r"BAŞLANGIÇ\s*TARİH[İI]\s*:\s*(\d{2}[./-]\d{2}[./-]\d{4})",
            r"SÖZLEŞME\s*TARİH[İI]\s*:\s*(\d{2}[./-]\d{2}[./-]\d{4})",
            r"TARİH[İI]\s*:\s*(\d{2}[./-]\d{2}[./-]\d{4})"
        ]
        
        for pat in date_patterns:
            m = re.search(pat, text_upper)
            if m:
                # Convert DD.MM.YYYY to YYYY-MM-DD for PHP / MySQL input
                raw_d = m.group(1).replace('.', '-').replace('/', '-')
                parts = raw_d.split('-')
                if len(parts) == 3:
                    start_date = f"{parts[2]}-{parts[1]}-{parts[0]}"
                break
        
        if not start_date:
            # Fallback to any date in document
            m = re.findall(r"(\d{2})[./-](\d{2})[./-](\d{4})", text)
            if m:
                start_date = f"{m[0][2]}-{m[0][1]}-{m[0][0]}"

        # City extract
        city_code = "01"
        city_name = None
        
        city_patterns = [
            r"İL[İI]?\s*:\s*([A-ZÇŞĞÜÖİ]+)",
            r"İL[İI]?\s*/\s*İLÇE[İI]?\s*:\s*([A-ZÇŞĞÜÖİ]+)"
        ]
        for pat in city_patterns:
            m = re.search(pat, text_upper)
            if m:
                possible_city = m.group(1).strip()
                if possible_city in PLATE_CODES:
                    city_name = possible_city
                    city_code = PLATE_CODES[possible_city]
                    break
        
        if not city_name:
            for city, code in PLATE_CODES.items():
                # Word boundary check for city names to avoid substrings (like 'VAN' matching in 'UNVAN')
                if re.search(r'\b' + re.escape(city) + r'\b', text_upper):
                    city_name = city
                    city_code = code
                    break

        print(json.dumps({
            "start_date": start_date,
            "city_name": city_name,
            "city_code": city_code
        }))

    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()
