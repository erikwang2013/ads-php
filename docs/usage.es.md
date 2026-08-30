# Guía de uso

[中文](docs/usage.md) | [English](docs/usage.en.md) | [한국어](docs/usage.ko.md) | [Русский](docs/usage.ru.md) | [Deutsch](docs/usage.de.md) | [Français](docs/usage.fr.md) | [Español](docs/usage.es.md) | [Português](docs/usage.pt.md) | [हिन्दी](docs/usage.hi.md) | [العربية](docs/usage.ar.md) | [বাংলা](docs/usage.bn.md) | [Bahasa Indonesia](docs/usage.id.md) | [日本語](docs/usage.ja.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

> Para la instalación y el despliegue, consulte la sección « Inicio rápido » del README; este documento cubre el flujo completo después de la instalación.

---

## 1. Primer inicio de sesión

Tras la instalación, abra la consola de administración:

- Instalación con un clic / Docker: `http://localhost`
- Desarrollo local: `http://localhost:8789`

Inicie sesión con el nombre de usuario y la contraseña de administrador establecidos en el asistente de instalación. Tras iniciar sesión verá el panel con 8 tarjetas de métricas KPI (coste total, impresiones, clics, conversiones, CTR, CVR, CPC medio, CPA medio), un gráfico de líneas de la tendencia diaria de costes, un gráfico de barras de comparación de plataformas y el TOP 10 de campañas.

Para cambiar su contraseña o datos de cuenta: Gestión del sistema → Gestión de usuarios.

---

## 2. Autorización de plataformas

El sistema admite **16 plataformas nacionales + 13 plataformas internacionales**, todas autorizadas mediante « Gestión de cuentas → Vincular cuenta ».

### Plataformas OAuth2 (la mayoría)

1. Seleccione la plataforma objetivo en la página « Vincular cuenta » y haga clic en « Autorizar »
2. El navegador redirige a la página de inicio de sesión de la plataforma; inicie sesión y apruebe el acceso
3. Tras la devolución de llamada, el sistema guarda automáticamente el token de acceso

Las plataformas autorizadas aparecen en la lista de cuentas. Los tokens caducados se renuevan automáticamente mediante `TokenRefreshTask` (en el minuto 55 de cada hora), sin intervención manual.

### Plataformas con clave API

Plataformas como Qihoo360, Sogou y Umeng usan autenticación por clave API: introduzca manualmente la clave API (y los parámetros de firma necesarios) en la página « Vincular cuenta », guarde y la sincronización comenzará.

> 16 plataformas nacionales: Juliang (Ocean Engine), Baidu Marketing, Taobao/Alimama, Tencent Ads, Kuaishou, Xiaohongshu, Weibo, Bilibili, Youku Ads, Meituan Ads, Zhihu Ads, Qihoo360, Sogou, Umeng, JD, Pinduoduo Ads
>
> 13 plataformas internacionales: Google Ads, YouTube Ads, Meta Ads, TikTok Ads, LinkedIn Ads, Snapchat Ads, Pinterest Ads, Twitter/X Ads, Amazon Ads, The Trade Desk, Spotify Ads, Twitch Ads, Netflix Ads

---

## 3. Vinculación de cuentas y subida a la biblioteca creativa

### Gestión de cuentas

Tras la autorización de la plataforma, las cuentas aparecen en la lista « Gestión de cuentas ». Cada cuenta puede controlar de forma independiente su participación en la sincronización (`sync_enabled`). La jerarquía publicitaria es de tres niveles: Campaña → Grupo de anuncios → Creativo.

### Biblioteca creativa

La « Biblioteca creativa » admite la subida de imágenes/vídeos con navegación tipo galería, para su uso en los creativos. Los recursos subidos pueden usar opcionalmente el almacenamiento CDN (ver más abajo).

### Configuración de proveedores de almacenamiento CDN

El sistema incluye una abstracción de almacenamiento con varios controladores; se pueden configurar varios proveedores a la vez:

| Controlador | Descripción |
|-------------|-------------|
| Almacenamiento local | Controlador predeterminado, guarda en el disco del servidor |
| Alibaba Cloud OSS | AlibabaOssStorage |
| Tencent Cloud COS | TencentCosStorage |
| Compatible S3 | S3CompatibleStorage (compatible con AWS S3, Qiniu Cloud, MinIO, etc.) |

Añada un proveedor en la página « Proveedor CDN » y rellene las claves/parámetros de región correspondientes para activarlo.

### Subida prefirmada y purga de caché

- **Subida prefirmada**: el servidor emite una URL prefirmada con límite de tiempo (PUT de OSS/S3) para cada subida; los navegadores o clientes móviles suben directamente al almacenamiento de objetos, sin pasar por el servidor de aplicaciones — menos ancho de banda y carga
- **Purga de caché**: tras actualizar o eliminar un recurso, se puede activar la purga de caché CDN para que los clientes reciban siempre el contenido más reciente

---

## 4. Sincronización de datos

La sincronización está impulsada por 6 tareas programadas (planificadas dentro del proceso mediante el plugin crontab de webman; no se necesita crontab externo):

| Tarea | Frecuencia | Responsabilidad |
|-------|------------|-----------------|
| RetrySyncTask | Cada 3 minutos | Reintentar la última sincronización fallida |
| AlertCheckTask | Cada 5 minutos | Evaluar las reglas de alerta |
| DataSyncTask | Cada 10 minutos | Sincronizar Campañas/Grupos/Creativos e informes (últimos 2 días, 9 métricas) |
| BidCheckTask | Cada 10 minutos | Comprobar las reglas de puja automática |
| BudgetCheckTask | Cada 15 minutos | Comprobaciones de alerta presupuestaria |
| TokenRefreshTask | Minuto 55 de cada hora | Renovar los tokens de plataforma caducados |

La configuración de las tareas está en `service/plugin/ads-task/config/cron.php`; las frecuencias se pueden modificar. El estado de sincronización se ve en la página « Sincronización de datos »; los interruptores por cuenta están en « Gestión de cuentas ».

---

## 5. Análisis de informes

### Panel

8 tarjetas de métricas KPI + gráfico de líneas de tendencia diaria + gráfico de barras de comparación de plataformas + TOP 10 de campañas, con filtro de rango de fechas y exportación a PDF/Excel con un clic.

### Informes personalizados

- **Dimensiones**: date, platform, campaign
- **Métricas**: cost, impressions, clicks, conversions, ctr, cvr, cpc, cpm, roi
- Admite consultas combinadas por dimensión y ordenación

### Análisis de atribución

Un motor de atribución multiplataforma integrado admite **5 modelos de atribución**: first_touch, last_touch, linear, time_decay, position_based, con una ventana de retroceso de 30 días. En la página « Análisis de atribución », elija un modelo y un rango de fechas para ver la contribución de cada canal.

### Calendario de campañas

El « Calendario de campañas » muestra el calendario de entrega de cada campaña en vista de calendario para un vistazo rápido al ritmo de entrega diario.

### Exportación

Los informes admiten tres formatos de exportación:

- **CSV** (BOM UTF-8, se abre directamente en Excel sin caracteres corruptos)
- **Excel** (HTML .xls)
- **PDF** (diseño de impresión HTML)

---

## 6. Alertas y notificaciones

### Reglas de alerta

Cree reglas en la página « Reglas de alerta »: elija el objeto supervisado (presupuesto/coste/impresiones/clics, etc.), el umbral y la comparación, el ámbito efectivo y los canales de notificación. Las reglas activadas se evalúan mediante `AlertCheckTask` cada 5 minutos y se activan al coincidir.

### Canales de notificación

| Canal | Descripción |
|-------|-------------|
| Web | Notificaciones dentro de la aplicación, visibles en el « Centro de notificaciones » |
| Email | Envío por correo (SMTP, con respaldo `mail()`) ; configure las direcciones de los destinatarios en la regla de alerta |
| SMS | Envío por SMS |
| Webhook | POST JSON a una URL de devolución de llamada configurada; integrable con WeCom/DingTalk/Feishu, etc. |

El historial de alertas se ve en la página « Registros de alertas ».

---

## 7. Aplicaciones móviles

### Aplicación Flutter (12 páginas: Inicio de sesión/Panel/Cuentas/Campañas/Grupos de anuncios/Creativos/Informes/Pujas/Alertas/Notificaciones, etc.)

```bash
cd apps/flutter
flutter run -d chrome     # PC Web
flutter run -d android    # Teléfono Android
```

### Aplicación HarmonyOS

Abra el directorio `apps/harmonyos` con DevEco Studio y ejecútelo.

---

## 8. Multiinquilino (Multi-tenancy)

El sistema incluye un plugin multiinquilino integrado (ads-tenant):

- **Identificación del inquilino**: el middleware `TenantIdentify` identifica el inquilino actual por solicitud
- **Aislamiento de datos**: dos modos — base de datos compartida aislada por `tenant_id`, o una base de datos independiente por inquilino (`db_type`)
- **Gestión de cuotas**: `QuotaService` valida las cuotas de los inquilinos (número de cuentas, recursos, etc.); las solicitudes que superen la cuota se rechazan

---

## Documentos relacionados

- [Funciones](features.es.md) — 21 módulos/flujos de negocio
- [Referencia de API](api.es.md) — todas las definiciones de interfaces
- [Arquitectura](architecture.es.md) — despliegue/seguridad/modelo de datos
