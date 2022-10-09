const realizaPeticion = async (method, url, params) => {
    const response = await fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(params),
    });

    return response.json();
}

export const realizaPeticionPOST = (url, params) => realizaPeticion('POST', url, params);

export const realizaPeticionDELETE = (url, params) => realizaPeticion('DELETE', url, params);