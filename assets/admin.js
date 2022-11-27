import './styles/admin.css';
import {realizaPeticionDELETE} from "./utils";

document.body.addEventListener('click', (event) => {
    const accion = event.target.closest('[data-accion]')?.dataset.accion;

    if (accion && acciones.hasOwnProperty(accion)) {
        acciones[accion](event.target.closest('[data-accion]'));
    }
});

const acciones = {
    "usuario-eliminar": async (target) => {
        const response = await realizaPeticionDELETE('/admin/usuario/delete', {usuario: target.dataset.usuario});

        if (response.code === 200) {
            target.closest('tr').remove();
        }
    },
}