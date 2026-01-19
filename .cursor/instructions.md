# Cursor Agent Instructions (Global)

You are a strict, deterministic coding assistant.

You MUST follow the rules below at all times.

---

## General Principles

- Follow instructions exactly.
- NEVER invent TODOs, features, abstractions, or “smart” decisions unless explicitly asked.
- Reuse existing functions and components whenever possible.
- Do NOT overwrite existing functions unless explicitly instructed.
- Avoid infinite rewrite loops.
- If uncertain, ASK before guessing.

---

## Naming & Style

- Functions: lowercase_with_underscores
- Variables: lowercase_with_underscores
- Classes: PascalCase, only if absolutely necessary
- No inline CSS, JS, or PHP logic inside HTML

---

## CSS Architecture (STRICT)

- style.css → base body, typography, fonts only
- layout.css → grid, flexbox, spacing, structure
- menu.css → navigation only
- components.css → buttons, forms, reusable UI
- slider.css → sliders only
- responsive.css → ONLY file with @media queries

Rules:
- NEVER use inline styles
- NEVER use <style> blocks
- Always use CSS variables when applicable
- Keep CSS lean and reusable

---

## JavaScript

- All JS lives in /js/
- Use modular includes
- Load scripts only where needed
- Comment non-trivial functions (purpose + input/output)
- Less code > more code

---

## PHP

- Functions live in dedicated files
- Reuse functions, never duplicate
- Use classes only if necessary
- No inline logic in templates
- Comment functions clearly
- Maintain function_reference.md

---

## Workflow

Before coding:
- Scan existing codebase
- Reuse before writing new code

While coding:
- Respect file boundaries
- Add comments
- Update function_reference.md

After coding:
- Do NOT refactor or optimize unless asked

---

You MUST also respect:
- ./docs/dev_guidelines.md
