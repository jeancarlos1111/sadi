import os

# Files to modify
files = [
    '/home/jean/Descargas/sadi/src/Repositories/ConvertidorCuentaRepository.php',
    '/home/jean/Descargas/sadi/src/Controllers/ConvertidorController.php',
    '/home/jean/Descargas/sadi/src/Models/ConvertidorCuenta.php',
    '/home/jean/Descargas/sadi/src/DTOs/ConvertidorCuentaDTO.php',
    '/home/jean/Descargas/sadi/views/contabilidad/convertidor_index.phtml',
    '/home/jean/Descargas/sadi/views/contabilidad/convertidor_vincular.phtml',
    '/home/jean/Descargas/sadi/views/layouts/sidebar.phtml',
    '/home/jean/Descargas/sadi/src/Repositories/PlanillaNominaRepository.php',
    '/home/jean/Descargas/sadi/src/Repositories/RecepcionAlmacenRepository.php',
    '/home/jean/Descargas/sadi/src/Repositories/SolicitudPagoRepository.php',
    '/home/jean/Descargas/sadi/src/Repositories/PresupuestoIngresoRepository.php'
]

replacements = {
    'ConvertidorCuentaRepository': 'VinculacionPucContableRepository',
    'ConvertidorController': 'VinculacionPucContableController',
    'ConvertidorCuentaDTO': 'VinculacionPucContableDTO',
    'ConvertidorCuenta': 'VinculacionPucContable',
    'convertidor_cuentas': 'vinculacion_puc_contable',
    'id_convertidor': 'id_vinculacion',
    'id_cuenta': 'id_cuenta_contable',
    'convertidor_index': 'vinculacion_index',
    'convertidor_vincular': 'vinculacion_vincular',
    'convertidor/index': 'vinculacion/index',
    'convertidor/vincular': 'vinculacion/vincular',
    'convertidor/guardarVinculo': 'vinculacion/guardarVinculo',
    'convertidor/eliminar': 'vinculacion/eliminar',
    "'convertidor'": "'vinculacion'",
    '"convertidor"': '"vinculacion"',
    'contabilidad/convertidor': 'contabilidad/vinculacion'
}

for file_path in files:
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        for old, new in replacements.items():
            content = content.replace(old, new)
            
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
            
# Rename the files
os.rename('/home/jean/Descargas/sadi/src/Repositories/ConvertidorCuentaRepository.php', '/home/jean/Descargas/sadi/src/Repositories/VinculacionPucContableRepository.php')
os.rename('/home/jean/Descargas/sadi/src/Controllers/ConvertidorController.php', '/home/jean/Descargas/sadi/src/Controllers/VinculacionPucContableController.php')
os.rename('/home/jean/Descargas/sadi/src/Models/ConvertidorCuenta.php', '/home/jean/Descargas/sadi/src/Models/VinculacionPucContable.php')
os.rename('/home/jean/Descargas/sadi/src/DTOs/ConvertidorCuentaDTO.php', '/home/jean/Descargas/sadi/src/DTOs/VinculacionPucContableDTO.php')

os.rename('/home/jean/Descargas/sadi/views/contabilidad/convertidor_index.phtml', '/home/jean/Descargas/sadi/views/contabilidad/vinculacion_index.phtml')
os.rename('/home/jean/Descargas/sadi/views/contabilidad/convertidor_vincular.phtml', '/home/jean/Descargas/sadi/views/contabilidad/vinculacion_vincular.phtml')

print("Renaming completed.")
