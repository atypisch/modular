# Cursor Auto Mode — MCP Orchestration

## JSON envelope for MCP calls

Every MCP call MUST use this envelope. Do not add keys. Do not omit required keys.

```json
{
  "program": "<plan|generate|refactor|validate|summarize>",
  "instructions": {
    "goal": "<string>",
    "project_stack": ["<string>", "..."],
    "scope": {},
    "temporary_constraints": {}
  },
  "token_estimate": {
    "planning": 0,
    "generation": 0,
    "summary": 0
  }
}
```

- `instructions.project_stack` is required. Example: `["vue","php","mysql"]` or `["vue","php","mysql","modular"]`.
- Send the envelope to `POST http://localhost:8080/execute` (or PORT from env). Parse the JSON response.

## Rules

1. **Never embed project context in prompts.** Do not paste codebase structure, file lists, or project rules into the prompt. Use only the goal and scope from the MCP envelope. Project context is carried by MCP state and summaries.

2. **Before continuing work:** Read MCP summaries from the runtime. Call `GET http://localhost:8080/status` and inspect `runtime_state` and any available summaries. If summaries exist, use them as the authoritative context for the next step. Do not re-request full schemas or full program output.

3. **After each execution:** Write a semantic summary and ensure it is stored. The MCP server appends to `runtime/summaries.json` with entries of the form:
   - `timestamp` (ISO8601)
   - `project_stack`
   - `goal`
   - `program`
   - `decisions` (string array)
   - `files_touched` (string array)
   - `constraints_applied` (string array)
   - `security_relevant` (boolean)
   If your execution did not trigger the server's summary (e.g. local-only step), produce the same structure and append it via a follow-up MCP call (e.g. summarize program) or document it for the next run.

4. **Auto mode behavior:** Assume abundant tokens. Use multi-step MCP programs (plan then generate, or generate then validate). Request full code generation. Perform file writes as specified by the MCP output. Do not skip steps to save tokens.

5. **Determinism:** Use the same envelope and the same program order for the same goal and project_stack. Do not vary instructions arbitrarily between runs.
