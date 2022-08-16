import './styles/ui.css'

document.querySelectorAll('.opcion-menu').forEach(el => {
    el.addEventListener('click', () => {
        let opcionActiva = document.querySelector('.opcion-menu-activa');
        if (opcionActiva) opcionActiva.classList.remove('opcion-menu-activa');

        el.classList.add('opcion-menu-activa');
    })
})