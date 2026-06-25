"""Build iron-gym exercises SQLite reference database.

Sources:
  - database/seeders/sql/exercises_seed.sql  (schema + lookup data + execution_description)

Output: database/database.sqlite

Usage: python .claude/scripts/build_exercises_sqlite.py
Requires: no extra dependencies (stdlib only)
"""
import sqlite3
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SQLITE_PATH = ROOT / "database" / "database.sqlite"
SQL_PATH    = ROOT / "database" / "seeders" / "sql" / "exercises_seed.sql"

# ── 1. Read SQL source ────────────────────────────────────────────────────────
with open(SQL_PATH, encoding="utf-8") as f:
    raw_sql = f.read()

# ── 2. Parse SQL into statements ──────────────────────────────────────────────
def split_sql(sql):
    """Split SQL on semicolons, respecting single-quoted strings."""
    stmts, current, in_string = [], [], False
    i = 0
    while i < len(sql):
        ch = sql[i]
        if in_string:
            current.append(ch)
            if ch == "'" and i + 1 < len(sql) and sql[i + 1] == "'":
                current.append(sql[i + 1])
                i += 2
                continue
            elif ch == "'":
                in_string = False
        elif ch == "'":
            in_string = True
            current.append(ch)
        elif ch == ";":
            stmts.append("".join(current).strip())
            current = []
        else:
            current.append(ch)
        i += 1
    if current:
        stmts.append("".join(current).strip())
    return stmts

def clean_stmt(s):
    kept = [l for l in s.splitlines() if not l.strip().startswith("--")]
    return "\n".join(kept).strip()

statements = [clean_stmt(s) for s in split_sql(raw_sql)]
statements = [s for s in statements if s and
              (s.upper().startswith("INSERT") or s.upper().startswith("UPDATE"))]

# ── 3. Create SQLite schema ───────────────────────────────────────────────────
con = sqlite3.connect(str(SQLITE_PATH))
con.execute("PRAGMA foreign_keys = ON")
cur = con.cursor()

for t in ["exercise_equipment", "exercise_muscle", "exercises",
          "equipment", "muscles", "movement_patterns"]:
    cur.execute(f"DROP TABLE IF EXISTS [{t}]")

cur.executescript("""
CREATE TABLE movement_patterns (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    slug          TEXT NOT NULL UNIQUE,
    name_it       TEXT NOT NULL,
    category      TEXT NOT NULL CHECK(category IN ('compound_pattern','joint_action')),
    display_order INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE muscles (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    slug          TEXT NOT NULL UNIQUE,
    name_it       TEXT NOT NULL,
    muscle_group  TEXT NOT NULL,
    muscle_head   TEXT,
    display_order INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE equipment (
    id      INTEGER PRIMARY KEY AUTOINCREMENT,
    slug    TEXT NOT NULL UNIQUE,
    name_it TEXT NOT NULL
);
CREATE TABLE exercises (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    slug                  TEXT NOT NULL UNIQUE,
    name_it               TEXT NOT NULL,
    compound_pattern_id   INTEGER REFERENCES movement_patterns(id),
    joint_action_id       INTEGER REFERENCES movement_patterns(id),
    mechanic              TEXT NOT NULL,
    plane                 TEXT NOT NULL,
    laterality            TEXT NOT NULL,
    skill_level           TEXT NOT NULL,
    measurement_type      TEXT NOT NULL,
    execution_description TEXT
);
CREATE TABLE exercise_muscle (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    exercise_id      INTEGER NOT NULL REFERENCES exercises(id),
    muscle_id        INTEGER NOT NULL REFERENCES muscles(id),
    role             TEXT NOT NULL CHECK(role IN ('primary','secondary','stabilizer')),
    contribution_pct INTEGER NOT NULL
);
CREATE TABLE exercise_equipment (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    exercise_id  INTEGER NOT NULL REFERENCES exercises(id),
    equipment_id INTEGER NOT NULL REFERENCES equipment(id)
);
""")

# ── 4. Populate from SQL seeder ───────────────────────────────────────────────
errors = []
for stmt in statements:
    try:
        cur.execute(stmt)
    except Exception as e:
        errors.append(f"ERROR: {e}\nSQL: {stmt[:120]}")

if errors:
    print(f"[WARN] {len(errors)} statement errors:")
    for err in errors[:5]:
        print(err)
else:
    print("[OK] All statements executed")

con.commit()

# ── 5. Verify ─────────────────────────────────────────────────────────────────
expected = {"movement_patterns": 27, "muscles": 26, "equipment": 14,
            "exercises": 83, "exercise_muscle": 259, "exercise_equipment": 108}
for table, exp in expected.items():
    count = cur.execute(f"SELECT COUNT(*) FROM [{table}]").fetchone()[0]
    print(f"  {table}: {count} [{'OK' if count == exp else f'MISMATCH expected {exp}'}]")

desc = cur.execute("SELECT COUNT(*) FROM exercises WHERE execution_description IS NOT NULL").fetchone()[0]
print(f"  exercises with description: {desc} [{'OK' if desc == 83 else 'MISMATCH expected 83'}]")
con.close()
print(f"[DONE] {SQLITE_PATH}")
