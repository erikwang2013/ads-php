# Fase 6: Refactorización de la Arquitectura Erik Stack

[中文](docs/superpowers/plans/2026-05-15-phase6-erik-stack.md) | [English](docs/superpowers/plans/2026-05-15-phase6-erik-stack.en.md) | [한국어](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ko.md) | [Русский](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ru.md) | [Deutsch](docs/superpowers/plans/2026-05-15-phase6-erik-stack.de.md) | [Français](docs/superpowers/plans/2026-05-15-phase6-erik-stack.fr.md) | [Español](docs/superpowers/plans/2026-05-15-phase6-erik-stack.es.md) | [Português](docs/superpowers/plans/2026-05-15-phase6-erik-stack.pt.md) | [हिन्दी](docs/superpowers/plans/2026-05-15-phase6-erik-stack.hi.md) | [العربية](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ar.md) | [বাংলা](docs/superpowers/plans/2026-05-15-phase6-erik-stack.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-05-15-phase6-erik-stack.id.md) | [日本語](docs/superpowers/plans/2026-05-15-phase6-erik-stack.ja.md)

> Refactorización integral: prefijo de base de datos, sistema de IDs, sistema de cifrado, copyright, estándares de código

## Lista de cambios

| # | Cambio | Paquete | Alcance de impacto |
|---|------|----|---------|
| 1 | Prefijo de tabla `ads_` | — | Todos los archivos SQL/migración |
| 2 | ID Snowflake como clave primaria (sin autoincremento) | erikwang2013/snowflake-php | Todos los Model + SQL |
| 3 | Cifrado/descifrado hashids de IDs de API | erikwang2013/hashids | Todas las respuestas de Controller |
| 4 | Cambio de autenticación JWT | erikwang2013/jwt-webman | AuthMiddleware + AuthController |
| 5 | Cifrado de datos sensibles de API | erikwang2013/encryption | Capa de solicitud/respuesta API |
| 6 | Cifrado de datos sensibles de BD | erikwang2013/encryptable | Capa de Model de Eloquent |
| 7 | Sincronización/consulta de datos ES | erikwang2013/webman-scout | Búsqueda de informes |
| 8 | Banderas de países | erikwang2013/season | Etiquetas de plataformas en frontend |
| 9 | Aviso de copyright | — | Cabecera de todos los archivos |
| 10 | Eliminar prefijo global `\` | — | Todos los archivos PHP |
| 11 | Comentarios en archivos de configuración | — | config/*.php |
| 12 | Diseño Web PC de Flutter | — | Proyecto Flutter |
| 13 | Mejora de visualización del panel Admin | — | Gráficos del dashboard |
| 14 | Exportación PDF de datos del panel | — | Nuevo formato de exportación |
| 15 | Exportación Excel (Client+Admin) | — | Exportación mejorada |
| 16 | App HarmonyOS | — | Nuevo proyecto HarmonyOS |

## Orden de implementación

**Batch A: Infraestructura (dependencias + ID + cifrado)**
- Actualizar composer.json añadiendo 6 paquetes erikwang2013
- Reescribir todos los archivos SQL de migración (prefijo ads_ + bigint sin autoincremento)
- Crear trait de ID Snowflake
- Actualizar todos los Model (usando SnowflakeTrait)
- Configurar middleware hashids
- Cambiar JWT a jwt-webman

**Batch B: Limpieza de código**
- Eliminar todos los prefijos globales `\`
- Añadir cabecera de copyright a todos los archivos
- Añadir comentarios a los archivos de configuración

**Batch C: Mejoras de frontend**
- Mejora de visualización del panel Admin (más gráficos, datos en tiempo real)
- Exportación PDF de datos del panel
- Mejora de exportación Excel

**Batch D: Flutter + HarmonyOS**
- Proyecto de diseño Web PC de Flutter
- Esqueleto del proyecto HarmonyOS
