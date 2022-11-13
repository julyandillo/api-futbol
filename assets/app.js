/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

require('@fortawesome/fontawesome-free/css/all.min.css');

import './styles/reset.css';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// start the Stimulus application
import './bootstrap';

export const modalEliminar = document.getElementById('modal-eliminar');

modalEliminar.querySelector('.modal-btn-cerrar').addEventListener('click', () => {
    modalEliminar.close();
})

export const muestraModalParaEliminarConMensaje = (mensaje, callable) => {
    modalEliminar.querySelector('.modal-msg').textContent = mensaje;
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