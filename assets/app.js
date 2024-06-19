import 'bootstrap-icons/font/bootstrap-icons.min.css';

import './styles/reset.css';
import './styles/app.css';
import './styles/ui.css';

export const modalEliminar = document.getElementById('modal-eliminar');
export const modalEditar = document.getElementById('modal-editar');


document.querySelectorAll('.modal-btn-cerrar').forEach(el => {
    el.addEventListener('click', (event) => {
        event.target.closest('dialog').close();
    });
});

export const muestraModalParaEliminarConMensaje = (mensaje, callable) => {
    modalEliminar.querySelector('.modal-msg').innerHTML = `<p>${mensaje}</p>`;
    modalEliminar.querySelector('.modal-btn-ok').onclick = () => {
        callable();
        modalEliminar.close();
    }

    modalEliminar.showModal();
}

export const muestraModalEditar = (label, callableParaOK) => {
    modalEditar.querySelector('label').textContent = label;
    modalEditar.querySelector('.modal-btn-ok').onclick = () => {
        callableParaOK();
        modalEditar.close();
    }
    modalEditar.showModal();
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