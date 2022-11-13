import './styles/usuarios.css'
import {realizaPeticionPOST} from "./utils";
import {muestraElemento, ocultaElemento} from "./app";

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
});