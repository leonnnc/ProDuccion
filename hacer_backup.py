# -*- coding: utf-8 -*-
import os
import zipfile
import sys

def crear_backup():
    nombre_zip = "backup_ProDuccion_v2.2.3.zip"
    directorio_raiz = os.path.abspath(os.path.dirname(__file__))
    ruta_zip = os.path.join(directorio_raiz, nombre_zip)

    # Exclusiones de directorios y archivos pesados o innecesarios
    exclusiones_directorios = {
        '.git',
        '.gemini',
        '.vscode',
        'win-g3000-1_3-n_mcd'
    }
    
    exclusiones_archivos = {
        nombre_zip,
        'win-g3000-1_3-n_mcd.exe',
        'hacer_backup.py'
    }

    print(u"--- Iniciando Backup del Proyecto ProDuccion ---")
    print(u"Directorio raíz: {}".format(directorio_raiz))
    print(u"Archivo destino: {}".format(nombre_zip))
    print(u"Excluyendo carpetas: {}".format(list(exclusiones_directorios)))
    print(u"Excluyendo archivos pesados: {}".format(list(exclusiones_archivos)))
    print("---------------------------------------------")

    total_archivos = 0
    total_bytes = 0

    try:
        with zipfile.ZipFile(ruta_zip, 'w', zipfile.ZIP_DEFLATED) as zip_file:
            for root, dirs, files in os.walk(directorio_raiz):
                # Modificar dirs in-place para que os.walk no recorra carpetas excluidas
                dirs[:] = [d for d in dirs if d not in exclusiones_directorios]

                for file in files:
                    if file in exclusiones_archivos:
                        continue
                    
                    # Excluir cualquier otro archivo zip existente de backup
                    if file.endswith('.zip'):
                        continue
                        
                    ruta_completa = os.path.join(root, file)
                    ruta_relativa = os.path.relpath(ruta_completa, directorio_raiz)
                    
                    zip_file.write(ruta_completa, ruta_relativa)
                    size = os.path.getsize(ruta_completa)
                    total_bytes += size
                    total_archivos += 1
                    
        print(u"\n¡Backup finalizado con éxito!")
        print(u"Total de archivos empaquetados: {}".format(total_archivos))
        print(u"Tamaño total sin comprimir: {:.2f} MB".format(total_bytes / (1024 * 1024)))
        print(u"Ubicación del Zip: {}".format(ruta_zip))
        
    except Exception as e:
        print(u"Error al generar el backup: {}".format(e))
        sys.exit(1)

if __name__ == '__main__':
    crear_backup()
