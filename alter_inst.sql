ALTER TABLE insttciones ADD (
    email VARCHAR2(150),
    password VARCHAR2(255)
);

-- Insertamos un registro falso o actualizamos uno existente para el test.
-- Buscamos el primer registro y le actualizamos email y password.
-- Para pruebas generaremos el hash de "secret123" en PHP, que es:
-- $2y$10$w6z/wOTL3B8KkG.I/oJ9GOUInQo0hT.q4aRIVv.O9a27H79F1QBWm (ejemplo bcrypt)
-- Para asegurar, actualizaremos el primer registro o insertaremos uno temporal.

EXIT;
