import sys
import json
import os
import tempfile
from pypdf import PdfWriter
from PIL import Image

def main():
    try:
        # Read file list from stdin
        input_data = sys.stdin.read()
        if not input_data:
            print(json.dumps({"error": "No input received"}))
            return
            
        file_list = json.loads(input_data)
        if not file_list:
            print(json.dumps({"error": "File list is empty"}))
            return
            
        merger = PdfWriter()
        temp_files_to_clean = []
        
        for item in file_list:
            filepath = item.get("path")
            if not filepath or not os.path.exists(filepath):
                continue
                
            ext = os.path.splitext(filepath)[1].lower()
            
            if ext == '.pdf':
                try:
                    merger.append(filepath)
                except Exception as e:
                    sys.stderr.write(f"Error appending PDF {filepath}: {str(e)}\n")
            elif ext in ['.jpg', '.jpeg', '.png', '.webp', '.gif']:
                try:
                    # Convert image to temporary PDF
                    img = Image.open(filepath)
                    if img.mode != 'RGB':
                        img = img.convert('RGB')
                        
                    fd, temp_pdf_path = tempfile.mkstemp(suffix='.pdf')
                    os.close(fd)
                    
                    img.save(temp_pdf_path, "PDF", resolution=100.0)
                    merger.append(temp_pdf_path)
                    temp_files_to_clean.append(temp_pdf_path)
                except Exception as e:
                    sys.stderr.write(f"Error converting image {filepath}: {str(e)}\n")
            else:
                sys.stderr.write(f"Unsupported format skipped: {filepath}\n")
                
        # Write merged output to a temporary file
        out_fd, output_pdf_path = tempfile.mkstemp(suffix='.pdf')
        os.close(out_fd)
        
        merger.write(output_pdf_path)
        merger.close()
        
        # Clean up temporary PDFs generated from images
        for temp_path in temp_files_to_clean:
            try:
                os.remove(temp_path)
            except:
                pass
                
        print(json.dumps({"success": True, "output_path": output_pdf_path}))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == '__main__':
    main()
