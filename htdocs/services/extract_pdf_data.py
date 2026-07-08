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

        lines = [line.strip() for line in text.split('\n')]
        
        # Clean parser variables
        start_date = None
        city_code = "01"
        city_name = None
        firma_adi = None
        adres = None
        sgk_no = None
        isg_katip_id = None
        
        # 1. Extract dates and contract ID
        for i, line in enumerate(lines):
            line_upper = tr_upper(line)
            
            # Contract ID
            if "SÖZLEŞME ID" in line_upper:
                for j in range(1, 10):
                    if i + j < len(lines):
                        potential_id = re.sub(r'\s+', '', lines[i + j])
                        if re.match(r'^\d{8}$', potential_id):
                            isg_katip_id = potential_id
                            break
                            
            # Start date
            if "BAŞLANGIÇ" in line_upper or "BAŞLANGIC" in line_upper:
                m_date = re.search(r"(\d{2})[./-](\d{2})[./-](\d{4})", line)
                if m_date:
                    start_date = f"{m_date.group(3)}-{m_date.group(2)}-{m_date.group(1)}"
                else:
                    for j in range(-2, 3):
                        if 0 <= i + j < len(lines):
                            m_date = re.search(r"(\d{2})[./-](\d{2})[./-](\d{4})", lines[i + j])
                            if m_date:
                                start_date = f"{m_date.group(3)}-{m_date.group(2)}-{m_date.group(1)}"
                                break
        
        # Fallback for start date if not found
        if not start_date:
            m = re.findall(r"(\d{2})[./-](\d{2})[./-](\d{4})", text)
            if m:
                start_date = f"{m[0][2]}-{m[0][1]}-{m[0][0]}"
                
        # 2. Extract Business Info
        for i, line in enumerate(lines):
            line_upper = tr_upper(line)
            
            if "HİZMET ALAN İŞYERİ" in line_upper or "HIZMET ALAN ISYERI" in line_upper:
                for j in range(1, 15):
                    if i + j < len(lines):
                        l = lines[i + j]
                        l_upper = tr_upper(l)
                        
                        # Adres
                        if "ADRES" in l_upper:
                            m_adr = re.search(r"ADRES\s*:?\s*(.*)", l, re.IGNORECASE)
                            if m_adr:
                                val = m_adr.group(1).strip()
                                if val.startswith(":"):
                                    val = val[1:].strip()
                                adres = val
                                
                        # SGK No
                        clean_l = re.sub(r'\s+', '', l)
                        if re.match(r'^\d{20,30}$', clean_l):
                            sgk_no = clean_l
                            
                        # Unvan / Firma Adı
                        if "UNVAN" in l_upper:
                            for k in range(1, 12):
                                if i + j + k < len(lines):
                                    candidate = lines[i + j + k].strip()
                                    if candidate.startswith(":"):
                                        candidate_val = candidate[1:].strip()
                                        candidate_upper = tr_upper(candidate_val)
                                        
                                        if "ADRES" in candidate_upper:
                                            continue
                                        if "İL" in candidate_upper and not "BİRLİĞİ" in candidate_upper and not "MİLLİ" in candidate_upper:
                                            continue
                                        if len(candidate_val) < 5:
                                            continue
                                            
                                        firma_adi = candidate_val
                                        break
                                        
        # City Mapping (using existing PLATE_CODES logic)
        for line in lines:
            line_upper = tr_upper(line)
            if "İL" in line_upper and ":" in line:
                parts = line.split(":")
                if len(parts) > 1:
                    possible_city = tr_upper(parts[1].strip())
                    if possible_city in PLATE_CODES:
                        city_name = possible_city
                        city_code = PLATE_CODES[possible_city]
                        break
                        
        if not city_name:
            # Fallback to general lookup
            for city, code in PLATE_CODES.items():
                if re.search(r'\b' + re.escape(city) + r'\b', text_upper):
                    city_name = city
                    city_code = code
                    break

        print(json.dumps({
            "start_date": start_date,
            "city_name": city_name,
            "city_code": city_code,
            "firma_adi": firma_adi,
            "adres": adres,
            "sgk_no": sgk_no,
            "isg_katip_id": isg_katip_id
        }))

    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    main()
