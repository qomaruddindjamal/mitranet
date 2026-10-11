import pycdlib
import io
import tarfile
import zstandard as zstd
import os

iso = pycdlib.PyCdlib()
iso.open('c:/mitranet/pfsense-offline-installer.iso')

def extract_pkg(iso_path, out_dir):
    os.makedirs(out_dir, exist_ok=True)
    bio = io.BytesIO()
    iso.get_file_from_iso_fp(bio, iso_path=iso_path)
    bio.seek(0)
    data = bio.read()
    print(f"Extracted {iso_path}, size={len(data)}")
    
    # Check if zstd
    try:
        dctx = zstd.ZstdDecompressor()
        decompressed = dctx.decompress(data, max_output_size=100*1024*1024)
        print(f"Decompressed with zstd, size={len(decompressed)}")
        t_io = io.BytesIO(decompressed)
    except Exception as e:
        print(f"Not zstd or raw tar: {e}")
        t_io = io.BytesIO(data)

    with tarfile.open(fileobj=t_io, mode='r:*') as t:
        print(f"Tar members ({len(t.getnames())}):")
        for m in t.getnames()[:25]:
            print("  ", m)
        t.extractall(path=out_dir)

print("=== 1. Extracting PFSENSE_PKG_WIREGUARD ===")
extract_pkg('/PACKAGES/ALL/PFSENSE_PKG_WIREGUARD_0_2_1.PKG;1', 'c:/mitranet/tmp/extracted_wireguard_pkg')

print("\n=== 2. Extracting XRAY_PFSENSE ===")
extract_pkg('/PACKAGES/ALL/XRAY_PFSENSE.PKG;1', 'c:/mitranet/tmp/extracted_xray_pkg')

iso.close()
