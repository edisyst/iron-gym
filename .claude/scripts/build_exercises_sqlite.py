"""Build iron-gym exercises SQLite reference database.

Sources:
  - database/seeders/sql/exercises_seed.sql  (schema + lookup data)
  - iron_gym_esercizi_descrizioni.xlsx       (execution_description per exercise)
  - .claude/docs/domain/exercises-catalog.md (name-to-slug mapping)

Output: database/database.sqlite

Usage: python .claude/scripts/build_exercises_sqlite.py
Requires: pip install openpyxl
"""
import sqlite3
import openpyxl
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SQLITE_PATH = ROOT / "database" / "database.sqlite"
MD_PATH     = ROOT / ".claude" / "docs" / "domain" / "exercises-catalog.md"
SQL_PATH    = ROOT / "database" / "seeders" / "sql" / "exercises_seed.sql"
XLSX_PATH   = ROOT / "iron_gym_esercizi_descrizioni.xlsx"

# ── 1. Read sources ───────────────────────────────────────────────────────────
with open(SQL_PATH, encoding="utf-8") as f:
    raw_sql = f.read()

with open(MD_PATH, encoding="utf-8") as f:
    md_lines = f.readlines()

# ── 2. Parse SQL into statements ──────────────────────────────────────────────
def clean_stmt(s):
    kept = [l for l in s.splitlines() if not l.strip().startswith("--")]
    return "\n".join(kept).strip()

statements = [clean_stmt(s) for s in raw_sql.split(";")]
statements = [s for s in statements if s and s.upper().startswith("INSERT")]

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
    print(f"[WARN] {len(errors)} INSERT errors:")
    for err in errors[:5]:
        print(err)
else:
    print("[OK] All INSERT statements executed")

# ── 5. Build name→slug mapping from .md ──────────────────────────────────────
# Pattern: **Italian name** · `slug` · ...
name_to_slug = {}
pat = re.compile(r"^\*\*(.+?)\*\*\s*\xb7\s*`([a-z0-9_]+)`")
for line in md_lines:
    m = pat.match(line.strip())
    if m:
        name_to_slug[m.group(1).strip()] = m.group(2).strip()

print(f"[OK] Mapped {len(name_to_slug)} exercise names to slugs from .md")

# ── 6. Add descriptions from Excel ───────────────────────────────────────────
wb = openpyxl.load_workbook(str(XLSX_PATH))
ws = wb.active

missing, updated = [], 0
for row in ws.iter_rows(min_row=2, values_only=True):
    name, description = row[0], row[1]
    if not name or not description:
        continue
    slug = name_to_slug.get(name)
    if not slug:
        missing.append(str(name))
        continue
    cur.execute(
        "UPDATE exercises SET execution_description = ? WHERE slug = ?",
        (str(description), slug),
    )
    updated += cur.rowcount or 0

print(f"[OK] Updated {updated} execution_description")
if missing:
    print(f"[WARN] Unmatched in Excel ({len(missing)}): {missing}")

con.commit()

# ── 7. Verify ─────────────────────────────────────────────────────────────────
expected = {"movement_patterns": 27, "muscles": 26, "equipment": 14,
            "exercises": 83, "exercise_muscle": 259, "exercise_equipment": 108}
for table, exp in expected.items():
    count = cur.execute(f"SELECT COUNT(*) FROM [{table}]").fetchone()[0]
    print(f"  {table}: {count} [{'OK' if count == exp else f'MISMATCH expected {exp}'}]")

desc = cur.execute("SELECT COUNT(*) FROM exercises WHERE execution_description IS NOT NULL").fetchone()[0]
print(f"  exercises with description: {desc}")
con.close()
print(f"[DONE] {SQLITE_PATH}")
