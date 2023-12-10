import './styles/usuarios.css'

import {realizaPeticionDELETE, realizaPeticionPOST} from "./utils.js";
import {htmlToElement} from "./ui.js";
import {
    modalEditar,
    muestraElemento,
    muestraModalEditar,
    muestraModalParaEliminarConMensaje,
    ocultaElemento
} from "./app.js";

const inputNombreAplicacion = document.getElementById('input__nombre-aplicacion');
const divNuevaAplicacion = document.getElementById('div__nueva-aplicacion');


document.body.addEventListener('click', (event) => {
    const accion = event.target.closest('[data-accion]')?.dataset.accion;

    if (accion) {
        acciones[accion](event);
    }
});

const acciones = {
    "app-token": (event) => {
        const elAplicacion = event.target.closest('.aplicacion');
        generaTokenParaAplicacion(elAplicacion.dataset.aplicacion)
            .then(token => {
                muestraElemento(elAplicacion.querySelector('.aplicacion-token'));
                elAplicacion.querySelector('.div__token').textContent = token;
            });
    },
    "app-token-copiar": async (event) => {
        await navigator.clipboard.writeText(event.target.closest('.aplicacion-token').querySelector('.div__token').textContent);

        const btnCopiar = event.target.closest('.aplicacion-copiar-token');
        btnCopiar.classList.add('aplicacion-copiar-token-success');
        const content = btnCopiar.innerHTML;
        btnCopiar.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;
        setTimeout(() => {
            btnCopiar.classList.remove('aplicacion-copiar-token-success');
            btnCopiar.innerHTML = content;
        }, 2000);
    },
    "app-eliminar": (event) => {
        const idAplicacion = event.target.closest('.aplicacion').dataset.aplicacion;
        const nombre = event.target.closest('.aplicacion').querySelector('.aplicacion-nombre').textContent;
        muestraModalParaEliminarConMensaje(`¿Seguro que quieres eliminar la aplicación <strong>${nombre}</strong>?`, async () => {
            await eliminaAplicacion(idAplicacion);
        });
    },
    "app-cambiar-nombre": (event) => {
        const idAplicacion = event.target.closest('.aplicacion').dataset.aplicacion;
        muestraModalEditar('Nombre', () => {
            const nuevoNombre = modalEditar.querySelector('#input__modal-editar').value;
            if (nuevoNombre.length > 0) modificaNombreAplicacion(idAplicacion, nuevoNombre);
        });
    },
}

document.getElementById('btn__guardar-aplicacion').addEventListener('click', async () => {
    const response = await realizaPeticionPOST('/nueva-aplicacion', {nombre: inputNombreAplicacion.value});

    if (response.code !== 200) {
        divNuevaAplicacion.querySelector('.error').textContent = response.msg;
        muestraElemento(divNuevaAplicacion.querySelector('.error'));
        return;
    }
    ocultaElemento(divNuevaAplicacion.querySelector('.error'));

    if (document.querySelector('.sin-aplicaciones')) {
        document.querySelector('.sin-aplicaciones').remove();
    }

    document.querySelector('.aplicaciones').appendChild(htmlToElement(response.html_aplicacion));
    inputNombreAplicacion.value = '';
});


const generaTokenParaAplicacion = async (id_aplicacion) => {
    const response = await realizaPeticionPOST('/token-aplicacion', {id_aplicacion});

    let divError = document.querySelector('.aplicacion-token .error');
    if (response.code !== 200) {
        divError.textContent = response.msg;
        muestraElemento(divError);
        return;
    }
    ocultaElemento(divError)

    return response.token;
}

const eliminaAplicacion = async (id_aplicacion) => {
    const response = await realizaPeticionDELETE('/elimina-aplicacion', {id_aplicacion});

    if (response.code === 200) {
        document.querySelector(`.aplicacion[data-aplicacion='${id_aplicacion}']`).remove();

        if (document.querySelectorAll('.aplicacion').length === 0) {
            document.querySelector('.aplicaciones')
                .appendChild(htmlToElement(`<h4 class="sin-aplicaciones">No hay ninguna aplicación configurada</h4>`));
        }
    }
}

const modificaNombreAplicacion = (idAplicacion, nombre) => {
    realizaPeticionPOST('/aplicacion-nombre', {id_aplicacion: idAplicacion, nombre})
        .then(response => {
            const elNombreAplicacion = document.querySelector(`.aplicacion[data-aplicacion="${idAplicacion}"] .aplicacion-nombre`);
            if (response.code === 200) elNombreAplicacion.textContent = nombre;
        });

}