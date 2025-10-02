-- Datos de ejemplo para eventos (basado en la imagen que compartiste)
USE web_calendar;

-- Eventos YT UN (11:00 - 12:00) de lunes a viernes
INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, created_by, recurrence_rule) VALUES
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-09-29 11:00:00', '2025-09-29 12:00:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-09-30 11:00:00', '2025-09-30 12:00:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-01 11:00:00', '2025-10-01 12:00:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-02 11:00:00', '2025-10-02 12:00:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'YT UN', 'Reunión YouTube Universidad', '2025-10-03 11:00:00', '2025-10-03 12:00:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR');

-- Eventos Gym (12:30 - 13:30) de lunes a viernes
INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, created_by, recurrence_rule) VALUES
(1, 'Gym', 'Sesión de ejercicio', '2025-09-29 12:30:00', '2025-09-29 13:30:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'Gym', 'Sesión de ejercicio', '2025-09-30 12:30:00', '2025-09-30 13:30:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-01 12:30:00', '2025-10-01 13:30:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-02 12:30:00', '2025-10-02 13:30:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR'),
(1, 'Gym', 'Sesión de ejercicio', '2025-10-03 12:30:00', '2025-10-03 13:30:00', 1, 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR');

-- Eventos de José
INSERT INTO events (calendar_id, title, description, start_datetime, end_datetime, created_by) VALUES
(5, 'José', 'Reunión con José', '2025-09-30 15:00:00', '2025-09-30 17:00:00', 2),
(5, 'José', 'Reunión con José', '2025-10-01 15:30:00', '2025-10-01 17:00:00', 2);

-- Permisos para compartir calendarios entre grupos
INSERT INTO calendar_permissions (calendar_id, group_id, permission_level, granted_by) VALUES
(2, 1, 'write', 1), -- Grupo Cumpleaños puede escribir en calendario Cumpleaños
(3, 2, 'write', 1), -- Grupo Tasks puede escribir en calendario Tareas
(4, 3, 'read', 1),  -- Grupo Festivos puede leer calendario Festivos
(5, 4, 'admin', 2); -- Grupo Tomás tiene acceso admin a su calendario