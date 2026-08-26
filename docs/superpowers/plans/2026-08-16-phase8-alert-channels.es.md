# Fase 8: Plan de Implementación — Implantación de Alertas Multicanal

[中文](docs/superpowers/plans/2026-08-16-phase8-alert-channels.md) | [English](docs/superpowers/plans/2026-08-16-phase8-alert-channels.en.md) | [한국어](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ko.md) | [Русский](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ru.md) | [Deutsch](docs/superpowers/plans/2026-08-16-phase8-alert-channels.de.md) | [Français](docs/superpowers/plans/2026-08-16-phase8-alert-channels.fr.md) | [Español](docs/superpowers/plans/2026-08-16-phase8-alert-channels.es.md) | [Português](docs/superpowers/plans/2026-08-16-phase8-alert-channels.pt.md) | [हिन्दी](docs/superpowers/plans/2026-08-16-phase8-alert-channels.hi.md) | [العربية](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ar.md) | [বাংলা](docs/superpowers/plans/2026-08-16-phase8-alert-channels.bn.md) | [Bahasa Indonesia](docs/superpowers/plans/2026-08-16-phase8-alert-channels.id.md) | [日本語](docs/superpowers/plans/2026-08-16-phase8-alert-channels.ja.md)

**Objetivo:** Cubrir el hueco restante de la Fase 5 — los canales email/sms de `NotificationService` pasan de stubs echo a implementaciones reales (correo SMTP + Webhook genérico), con soporte de configuración de canales. El canal web y el pub/sub de Redis ya están implementados y no cambian.

**Fuente:** Conclusión de la auditoría del equipo de la Fase 7 (comparación de la planificación del researcher: el único elemento marcado claramente como "parcialmente completado" = canales múltiples de alertas de la Fase 5; a `ads-alert` le falta el directorio `channel/`)

**Tech Stack:** webman v2 (PHP 8.2+), PHPMailer (SMTP), Redis, Vue 3 + Element Plus

---

## Estado actual (verificado)

| Componente | Estado |
|---|---|
| `NotificationService::send()` | `match ($channel)` distribuye web/email/sms; web escribe realmente en `erik_notifications`, email/sms son stubs echo |
| `AlertRule.channels` | Campo JSON + cast array de Eloquent; el frontend ya envía `['web','email','sms']` |
| Admin AlertRuleList.vue | Ya tiene UI de selección de canales (web bloqueado, email/sms opcionales) |
| Redis pub/sub | Canal `alert:new` implementado |
| Configuración SMTP/correo | No existe (service/config sin configuración de mail) |

## Tarea 1: Canal de correo (SMTP)

### Archivos:
- Crear: `service/config/mail.php` (smtp host/port/user/pass/from/from_name/encryption, impulsado por env)
- Crear: `service/plugin/ads-alert/service/channel/EmailChannel.php` (implementa send(AlertLog, AlertRule))
- Modificar: `service/plugin/ads-alert/service/NotificationService.php` (la rama email llama a EmailChannel; eliminar el stub echo)
- Modificar: `service/composer.json` (si se elige PHPMailer hay que añadir la dependencia; se prefiere considerar una implementación sin dependencias con `mail()`/socket para mantener la ligereza; lo evalúa quien implementa)

### Puntos de diseño
- Destinatario: leer de la configuración de AlertRule o del tenant (si no hay, usar el campo `email` o el valor por defecto de configuración)
- Asunto/cuerpo: reutilizar la plantilla de texto de sendWeb ("Alerta disparada: {rule.name}" + métrica/valor actual/condición/umbral)
- Manejo de fallos: capturar excepciones y registrar en log; no afecta a los demás canales ni al flujo principal
- Degradación elegante si falta configuración (aviso en log; no lanzar excepción que interrumpa)

## Tarea 2: Canal Webhook

### Archivos:
- Crear: `service/plugin/ads-alert/service/channel/WebhookChannel.php` (POST JSON a la URL configurada)
- Modificar: la rama `'webhook'` en `NotificationService::send()`

### Puntos de diseño
- Origen de la configuración: AlertRule amplía el campo `webhook_url` (migración) o configuración de channels; para el cambio mínimo, se prefiere añadir la columna `webhook_url` en AlertRule (nullable)
- Carga útil: `{event: 'alert.triggered', alert: {...}, rule: {...}, timestamp}`, incluye nivel de alerta/métrica/valor/umbral/hora
- Tiempo de espera y reintentos: tiempo de espera de conexión 5s, tiempo de espera total 10s; los fallos se registran en log (sin reintentos, para mantener la simplicidad)
- Seguridad: solo permitir http/https; no validar direcciones de red interna (el riesgo SSRF se anota como limitación conocida, o validar que no sea red interna — lo evalúa y documenta quien implementa)

## Tarea 3: Canal SMS (placeholder de pasarela)

### Archivos:
- Modificar: `NotificationService::sendSms` (conservar el placeholder, comentar claramente el punto de integración; si quien implementa evalúa que hay una solución ligera, puede implantarla)

### Puntos de diseño
- Las pasarelas SMS (Alibaba Cloud/Tencent Cloud) requieren AK/SK y pago; esta fase conserva la implementación placeholder con comentarios que indican los pasos de integración
- La opción sms de la UI del frontend se mantiene seleccionable, pero el backend solo registra en log (informar claramente al usuario de que no hay pasarela configurada)

## Tarea 4: Configuración de canales y frontend

### Archivos:
- Modificar: `admin/public/web/src/views/alert/AlertRuleList.vue` (si se añade la opción webhook y la entrada de URL)
- Modificar: `service/plugin/ads-api/controller/v1/AlertController.php` (la creación/actualización de reglas acepta webhook_url)
- Modificar: `service/plugin/ads-alert/model/AlertRule.php` (añadir webhook_url a fillable/casts)
- Modificar: `service/plugin/ads-alert/migration/create_alerts.sql` (ALTER o script incremental documentado)

### Aceptación
- [ ] Canal email: tras configurar SMTP, la alerta disparada llega al correo; sin configuración, degradación elegante
- [ ] Canal webhook: al dispararse la alerta, POST JSON a la URL configurada con todos los campos de la carga útil
- [ ] Canal sms: mantiene el placeholder, registra en log
- [ ] El canal web y el pub/sub de Redis no se ven afectados en la regresión
- [ ] El formulario de reglas de Admin permite configurar los nuevos campos de canal
- [ ] `php vendor/bin/phpunit --no-coverage` pasa completo
- [ ] Tests nuevos/actualizados: tests de distribución de canales de AlertEngine/NotificationService
