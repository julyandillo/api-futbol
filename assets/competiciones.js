import {muestraModalParaEliminarConMensaje} from "./app";
import {realizaPeticionPOST} from "./utils";

document.addEventListener('DOMContentLoaded', () => {
    cargaTagsTiposDeCompeticion();
});

const cargaTagsTiposDeCompeticion = () => {
    const categorias = {};
    const competiciones = document.querySelectorAll('.competicion');
    const competicionesTags = document.querySelector('.competiciones-tags');

    competicionesTags.innerHTML = '';

    competiciones.forEach((el) => {
        let categoria = el.dataset.categoria;
        if (categoria in categorias) {
            categorias[categoria] += 1;
        } else {
            categorias[categoria] = 1;
        }
    });

    if (competiciones.length > 0) {
        competicionesTags.appendChild(
            creaBadgeCategoriaCompeticion('Todas', competiciones.length)
        );
    }

    for (let [categoria, cantidad] of Object.entries(categorias)) {
        competicionesTags.appendChild(
            creaBadgeCategoriaCompeticion(categoria, cantidad)
        );
    }
}

const creaBadgeCategoriaCompeticion = (categoria, cantidad) => {
    const badge = document.createElement('span');
    badge.textContent = nombreParaCategoria(categoria);
    badge.classList.add('badge');
    badge.dataset.categoria = categoria;

    const cantidadBadge = document.createElement('span');
    cantidadBadge.textContent = cantidad;
    cantidadBadge.classList.add('badge-cantidad');

    badge.appendChild(cantidadBadge);
    badge.onclick = () => {
        document.querySelectorAll('.badge-activo').forEach(el => el.classList.remove('badge-activo'))
        if (categoria === 'Todas') {
            muestraTodasLasCompeticiones();
        } else {
            badge.classList.add('badge-activo');
            muestraCompeticionesPorCategoria(categoria);
        }
    }

    return badge;
}

const nombreParaCategoria = (categoria) => {
    return categoria.replaceAll('_', ' ');
}

const muestraCompeticionesPorCategoria = (categoria) => {
    competiciones.forEach((competicion) => {
        competicion.style.display = (competicion.dataset.categoria !== categoria) ? 'none' : 'flex';
    });
}

const muestraTodasLasCompeticiones = () => {
    competiciones.forEach(el => {
        el.style.display = 'flex';
    })
}

document.querySelectorAll('[data-accion]').forEach(el => {
    el.addEventListener('click', (event) => {
        const target = event.target.closest('[data-accion]');
        const accionParaEjecutar = target.dataset.accion;
        const acciones = {
            eliminar: () => muestraConfirmacionParaEliminarCompeticion(target.dataset.competicion),
        }

        if (!acciones.hasOwnProperty(accionParaEjecutar)) {
            return;
        }

        acciones[accionParaEjecutar]();
    });
});

const muestraConfirmacionParaEliminarCompeticion = async (idCompeticion) => {
    muestraModalParaEliminarConMensaje('¿seguro que quieres eliminar la competición?', () => {eliminaCompeticion(idCompeticion)});
}

const eliminaCompeticion = async (competicion) => {
    const response = await realizaPeticionPOST('/competicion-eliminar', {competicion});

    if (response.code === 200) {
        document.querySelector(`[data-competicion="${competicion}"]`).closest('.competicion').remove();
        cargaTagsTiposDeCompeticion();
    }
}

