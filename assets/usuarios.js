import './styles/usuarios.css'
import {realizaPeticionDELETE, realizaPeticionPOST} from "./utils";
import {muestraElemento, ocultaElemento} from "./app";
import {htmlToElement} from "./ui";

const inputNombreAplicacion = document.getElementById('input__nombre-aplicacion');
const divNuevaAplicacion = document.getElementById('div__nueva-aplicacion');

document.getElementById('btn__guardar-aplicacion').addEventListener('click', async () => {
    const response = await realizaPeticionPOST('/nueva-aplicacion', {nombre: inputNombreAplicacion.value});

    if (response.code !== 200) {
        divNuevaAplicacion.querySelector('.error').textContent = response.msg;
        muestraElemento(divNuevaAplicacion.querySelector('.error'));
        return;
    }
    ocultaElemento(divNuevaAplicacion.querySelector('.error'));

    document.querySelector('.aplicaciones').appendChild(htmlToElement(response.html_aplicacion));
    inputNombreAplicacion.value = '';
});

document.querySelectorAll('.btn__aplicacion-token').forEach(el => {
    el.addEventListener('click', () => {
        const elAplicacion = el.closest('.aplicacion');
        generaTokenParaAplicacion(el.dataset.aplicacion)
            .then(token => {
                muestraElemento(elAplicacion.querySelector('.aplicacion-token'));
                elAplicacion.querySelector('.div__token').textContent = token;
            });
    });
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

document.querySelectorAll('.aplicacion-copiar-token').forEach(el => {
    el.addEventListener('click', () => {
        navigator.clipboard.writeText(el.closest('.aplicacion-token').querySelector('.div__token').textContent);
        el.classList.add('aplicacion-copiar-token-success');
        const content = el.innerHTML;
        el.innerHTML = `<i class="fa-solid fa-circle-check"></i>`;
        setTimeout(() => {
            el.classList.remove('aplicacion-copiar-token-success');
            el.innerHTML = content;
        }, 2500);
    });
});

document.querySelectorAll('.btn__aplicacion-eliminar').forEach(el => {
    el.addEventListener('click', () => {
        const idAplicacion = el.closest('.aplicacion').dataset.aplicacion;

        eliminaAplicacion(idAplicacion)
            .then(response => {
                if (response.code === 200) {
                    document.querySelector(`.aplicacion[data-aplicacion='${idAplicacion}']`).remove();
                }
            });
    });
});

const eliminaAplicacion = async (id_aplicacion) => {
    return await realizaPeticionDELETE('/elimina-aplicacion', {id_aplicacion});
}