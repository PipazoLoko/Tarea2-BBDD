import argparse
import random
import re
import time
import hashlib
from datetime import datetime, timedelta

# pip install mysql-connector-python
import mysql.connector

HOST = "localhost"
PORT = 3306
USER = "root"
PASSWORD = "Undiaviunavaca123."
DATABASE = "tarea2"

TOPICOS = ["Backend", "Seguridad", "UI/UX"]
ESTADOS = ["Abierto", "En Progreso", "Resuelto", "Cerrado"]
TIPOS   = ["Error", "Funcionalidad"]
AMBIENTES = ["Web", "Movil"]


FIRST_NAMES = [
    "Ana","Bruno","Carla","Diego","Elena","Fabian","Gabriela","Hector","Ines","Javier",
    "Karen","Luis","María","Nicolás","Olga","Pablo","Quica","Rodolfo","Sofía","Tomás",
    "Úrsula","Valeria","Walter","Ximena","Yago","Zoe",
]
LAST_NAMES = [
    "Pérez","Díaz","Soto","Rivas","Muñoz","González","Fernández","Ramírez","Torres","Vargas",
    "Castro","Morales","Navarro","Ortega","Pizarro","Quinteros","Reyes","Salinas","Toledo",
    "Ulloa","Valdés","Weber","Xavier","Yáñez","Zúñiga",
]


def rand_date(start_year=2016, end_year=datetime.now().year):
    start = datetime(start_year, 1, 1)
    end = datetime(end_year, 12, 31)
    delta = end - start
    return start + timedelta(days=random.randint(0, delta.days))


def gen_unique_ruts(n, existing=None):
    existing = set(existing or [])
    ruts = []
    while len(ruts) < n:
        base = random.randint(10_000_000, 99_999_999)
        dv = random.choice(list("0123456789K"))
        rut = f"{base}-{dv}"
        rut = re.sub(r"\s+", "", rut)
        if rut not in existing:
            existing.add(rut)
            ruts.append(rut)
    return ruts


def sanitize_email(name, i):
    base = (
        name.lower()
        .replace(" ", ".")
        .replace("á","a").replace("é","e").replace("í","i")
        .replace("ó","o").replace("ú","u").replace("ñ","n")
    )
    return f"{base}{i}@example.com"


def connect_mysql(host, port, user, password, database):
    return mysql.connector.connect(
        host=host, port=port, user=user, password=password, database=database,
        autocommit=False
    )


def ensure_catalogs(cur):
    cur.executemany(
        "INSERT IGNORE INTO topico (topico) VALUES (%s)",
        [(t,) for t in TOPICOS]
    )
    # estado
    cur.executemany(
        "INSERT IGNORE INTO estado (estado) VALUES (%s)",
        [(e,) for e in ESTADOS]
    )
    # tipo
    cur.executemany(
        "INSERT IGNORE INTO tipo (tipo) VALUES (%s)",
        [(t,) for t in TIPOS]
    )
    
    # ambiente
    cur.executemany(
        "INSERT IGNORE INTO ambiente (id_ambiente) VALUES (%s)",
        [(a,) for a in AMBIENTES]
    )



def insert_users(cur, total_users):
    people = []
    for i in range(total_users):
        name = f"{random.choice(FIRST_NAMES)} {random.choice(LAST_NAMES)}"
        email = sanitize_email(name, i)
        raw_pwd = name.split()[0].capitalize() + "123!"
        pwd_hash = hashlib.sha256(raw_pwd.encode("utf-8")).hexdigest()
        people.append((name, email, pwd_hash))

    ruts = gen_unique_ruts(len(people))
    payload = [(r, n, e, p) for r, (n, e, p) in zip(ruts, people)]

    cur.executemany(
        """
        INSERT IGNORE INTO usuario (rut, nombre, email, contrasena)
        VALUES (%s, %s, %s, %s)
        """,
        payload
    )

    return [rut for rut, *_ in payload]


def pick_distinct(pop, k):
    k = min(k, len(pop))
    return random.sample(pop, k)


def upsert_engineers_and_specialties(cur, engineer_ruts):
    """
    Inserta ingenieros y les asigna 1–2 TOPICOS
    (respeta trigger de máximo 2 especialidades).
    """
    if not engineer_ruts:
        return

    cur.executemany(
        "INSERT IGNORE INTO ingeniero (rut) VALUES (%s)",
        [(r,) for r in engineer_ruts]
    )

    for rut in engineer_ruts:
        topics = pick_distinct(TOPICOS, random.randint(1, 2))
        for t in topics:
            try:
                cur.execute(
                    "INSERT INTO ingeniero_especialidad (rut, topico) VALUES (%s, %s)",
                    (rut, t)
                )
            except mysql.connector.Error as e:
                if e.errno != 1644:
                    raise

    cur.execute("""
        SELECT topico, COUNT(*) FROM ingeniero_especialidad GROUP BY topico
    """)
    counts = {t: c for t, c in cur.fetchall()}
    if engineer_ruts:
        for t in TOPICOS:
            if counts.get(t, 0) == 0:
                try:
                    cur.execute(
                        "INSERT INTO ingeniero_especialidad (rut, topico) VALUES (%s, %s)",
                        (engineer_ruts[0], t)
                    )
                except mysql.connector.Error as e:
                    if e.errno != 1644:
                        raise


def fetch_ids(cur, sql):
    cur.execute(sql)
    return [x[0] for x in cur.fetchall()]


def compose_title(prefix, i, run_tag):
    words = [
        "autenticacion","sesion","rendimiento","usabilidad","carga","perfil",
        "catalogo","cesta","pago","notificaciones","tema","movil","web","seguridad","api"
    ]
    base = f"{prefix} #{i:04d} - " + " ".join(random.sample(words, 3))
    return f"{base} [{run_tag}]"


def insert_requests_and_criteria(cur, author_ruts, n_error, n_feature, ambientes_min=2, run_tag="run"):
    ambiente_ids = fetch_ids(cur, "SELECT id_ambiente FROM ambiente")
    if not ambiente_ids:
        cur.executemany("INSERT INTO ambiente () VALUES ()", [(), ()])
        ambiente_ids = fetch_ids(cur, "SELECT id_ambiente FROM ambiente")

    created_ids = []

    for i in range(n_error):
        titulo = compose_title("Error", i, run_tag)
        descripcion = "Incidencia detectada y reproducida."
        ambiente = random.choice(ambiente_ids)
        topico = random.choice(TOPICOS)
        autor = random.choice(author_ruts)
        estado = random.choice(ESTADOS)
        fecha = rand_date()

        cur.execute(
            """
            INSERT IGNORE INTO solicitud
            (titulo, tipo, descripcion, ambiente, topico, autor, estado, fecha_publicacion)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """,
            (titulo, "Error", descripcion, ambiente, topico, autor, estado, fecha)
        )

    for i in range(n_feature):
        titulo = compose_title("Funcionalidad", i, run_tag)
        descripcion = "Requisito acotado y validado."
        ambiente = random.choice(ambiente_ids)
        topico = random.choice(TOPICOS)
        autor = random.choice(author_ruts)
        estado = random.choice(ESTADOS)
        fecha = rand_date()

        cur.execute(
            """
            INSERT IGNORE INTO solicitud
            (titulo, tipo, descripcion, ambiente, topico, autor, estado, fecha_publicacion)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """,
            (titulo, "Funcionalidad", descripcion, ambiente, topico, autor, estado, fecha)
        )

        cur.execute("SELECT id_solicitud FROM solicitud WHERE titulo=%s", (titulo,))
        row = cur.fetchone()
        if row:
            sid = row[0]
            created_ids.append((sid, topico))

            for _ in range(3):
                cur.execute(
                    "INSERT INTO criterio (id_solicitud, descripcion) VALUES (%s, %s)",
                    (sid, "Criterio " + random.choice("ABCDEFGHIJKLMNOPQRSTUVWXYZ"))
                )

    return created_ids


def assign_engineers(cur, created_ids):
    """
    Asigna ingenieros a solicitudes respetando:
      - topico compatible con especialidad (FK compuesta)
      - <= 3 ingenieros por solicitud (trigger + chequeo local)
      - <= 25 solicitudes por ingeniero (chequeo local)
    """
    cur.execute("""
        SELECT e.topico, GROUP_CONCAT(e.rut) AS ruts
        FROM ingeniero_especialidad e
        GROUP BY e.topico
    """)
    eng_by_topic = {}
    for topico, ruts_csv in cur.fetchall():
        eng_by_topic[topico] = ruts_csv.split(",") if ruts_csv else []

    for sid, topico in created_ids:
        candidates = eng_by_topic.get(topico, []).copy()
        random.shuffle(candidates)

        cur.execute("SELECT n_ingenieros FROM solicitud WHERE id_solicitud=%s", (sid,))
        current = cur.fetchone()[0]
        to_assign = max(0, 3 - current)
        if to_assign == 0:
            continue

        assigned = 0
        for rut in candidates:
            if assigned >= to_assign:
                break

            cur.execute("SELECT COUNT(*) FROM ingeniero_funcionalidad WHERE rut=%s", (rut,))
            carga = cur.fetchone()[0]
            if carga >= 25:
                continue

            try:
                cur.execute(
                    "INSERT INTO ingeniero_funcionalidad (rut, id_solicitud, topico) VALUES (%s, %s, %s)",
                    (rut, sid, topico)
                )
                assigned += 1
            except mysql.connector.Error as e:
                if e.errno in (1062, 1452, 1644):
                    continue
                else:
                    raise


def main():
    ap = argparse.ArgumentParser(description="Poblar base MySQL tarea2")
    ap.add_argument("--host", default=HOST)
    ap.add_argument("--port", type=int, default=PORT)
    ap.add_argument("--user", default=USER)
    ap.add_argument("--password", default=PASSWORD)
    ap.add_argument("--database", default=DATABASE)

    ap.add_argument("--users", type=int, default=80)
    ap.add_argument("--authors", type=int, default=50)
    ap.add_argument("--engineers", type=int, default=50)
    ap.add_argument("--errors", type=int, default=300)
    ap.add_argument("--features", type=int, default=200)
    ap.add_argument("--ambientes", type=int, default=2)
    ap.add_argument("--seed", type=int, default=123)
    ap.add_argument("--tag", type=str, default=None)

    args = ap.parse_args()
    random.seed(args.seed)
    run_tag = args.tag or str(int(time.time()))

    conn = connect_mysql(args.host, args.port, args.user, args.password, args.database)
    cur = conn.cursor()

    try:
        ensure_catalogs(cur)

        inserted_ruts = insert_users(cur, args.users)

        cur.execute("SELECT rut FROM usuario")
        all_ruts = [r for (r,) in cur.fetchall()]

        authors = pick_distinct(all_ruts, args.authors)
        engineers = pick_distinct(all_ruts, args.engineers)

        upsert_engineers_and_specialties(cur, engineers)

        created_ids = insert_requests_and_criteria(
            cur, authors, args.errors, args.features, ambientes_min=args.ambientes, run_tag=run_tag
        )

        assign_engineers(cur, created_ids)

        conn.commit()

        cur.execute("SELECT COUNT(*) FROM usuario")
        c_users = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM ingeniero")
        c_eng = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM solicitud WHERE tipo='Error'")
        c_err = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM solicitud WHERE tipo='Funcionalidad'")
        c_feat = cur.fetchone()[0]
        cur.execute("SELECT COUNT(*) FROM criterio")
        c_crit = cur.fetchone()[0]

        print(f"OK. Usuarios:{c_users} | Ingenieros:{c_eng} | Error:{c_err} | Funcionalidad:{c_feat} | Criterios:{c_crit}")

    finally:
        cur.close()
        conn.close()


if __name__ == "__main__":
    main()
