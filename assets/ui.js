import './styles/ui.css'

document.querySelectorAll('.opcion-menu').forEach(el => {
    el.addEventListener('click', () => {
        let opcionActiva = document.querySelector('.opcion-menu-activa');
        if (opcionActiva) opcionActiva.classList.remove('opcion-menu-activa');

        el.classList.add('opcion-menu-activa');
    })
});

export const htmlToElement = (html) => {
    const template = document.createElement('template');
    template.innerHTML = html;
    return template.content.firstChild;
}