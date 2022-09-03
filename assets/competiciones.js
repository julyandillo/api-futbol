const categorias = {};

const competiciones = document.querySelectorAll('.competicion');
const competicionesAcciones = document.querySelector('.competiciones-acciones');

document.addEventListener('DOMContentLoaded', () => {
    competiciones.forEach((el) => {
        let categoria = el.dataset.categoria;
        if (categoria in categorias) {
            categorias[categoria] += 1;
        } else {
            categorias[categoria] = 1;
        }
    });

    if (competiciones.length > 0) {
        competicionesAcciones.appendChild(
            creaBadgeCategoriaCompeticion('Todas', competiciones.length)
        );
    }

    for (let [categoria, cantidad] of Object.entries(categorias)) {
        competicionesAcciones.appendChild(
            creaBadgeCategoriaCompeticion(categoria, cantidad)
        );
    }
});

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
            muestraTodasLasCompeticiones()
        } else {
            badge.classList.add('badge-activo');
            muestraCompeticionesConCategoria(categoria);
        }
    }

    return badge;
}

const nombreParaCategoria = (categoria) => {
    return categoria.replaceAll('_', ' ');
}

const muestraCompeticionesConCategoria = (categoria) => {
    competiciones.forEach((competicion) => {
        competicion.style.display = (competicion.dataset.categoria !== categoria) ? 'none' : 'flex';
    });
}

const muestraTodasLasCompeticiones = () => {
    competiciones.forEach(el => {
        el.style.display = 'flex';
    })
}