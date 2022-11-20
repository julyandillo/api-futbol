/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

require('@fortawesome/fontawesome-free/css/all.min.css');

import './styles/reset.css';
import './styles/app.css';

export const modalEliminar = document.getElementById('modal-eliminar');

modalEliminar.querySelector('.modal-btn-cerrar').addEventListener('click', () => {
    modalEliminar.close();
})

export const muestraModalParaEliminarConMensaje = (mensaje, callable) => {
    modalEliminar.querySelector('.modal-msg').innerHTML = `<p>${mensaje}</p>`;
    modalEliminar.querySelector('.modal-btn-ok').onclick = () => {
        callable();
        modalEliminar.close();
    }
    
    modalEliminar.showModal();
}

document.querySelectorAll('[data-toggle]').forEach(el => {
    el.addEventListener('click', event => {
        const element = document.getElementById(event.target.closest('[data-toggle]').dataset.toggle);
        if (!element) return;
        element.classList.toggle('oculto');
    });
});

export const ocultaElemento = el => el.classList.add('oculto');

export const muestraElemento = el => el.classList.remove('oculto');