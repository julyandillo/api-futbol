# api-futbol

API desarrollada en Symfony para gestionar estadísticas sobre competiciones de futbol.

Se podrán mantemer datos sobre competiciones, jugadores, equipos, estadios, partidos (incluyendo los eventos que ocurran en dichos partidos) y un histórico de estadísticas para consultar en cualquier momento.

Una competición podrá ser de dos tipos, liga (38 jornadas de 10 partidos) o torneo (fase de grupos y eliminatorias). En ambos casos se guardará información de que equipos y jugadores componen dicha competición y de todos los partidos que se disputan, incluyendo estadísticas tanto de jugadores, equipos como de partidos para cada jornada.

Con el creador de competiciones podemos decidir que tipo de competición estamos creando y configurar equipos, jugadores, grupos y eliminatorias.