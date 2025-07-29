# Using CodeRabbit for Local AI Code Reviews

> **CodeRabbit** is an AI-powered code-review assistant that integrates with VS Code, Cursor, and any other VS Code-compatible editor.  It performs automated, context-aware reviews of your staged or committed changes and can even apply suggested fixes through your editor’s built-in AI agent.

## Why Add CodeRabbit to Your Workflow?

* **Immediate feedback** – Run a first-pass code review before pushing or opening a PR.
* **Catch hidden issues** – Spots bugs, code smells, missing tests, and security vulnerabilities.
* **One-click fixes** – Apply inline suggestions directly, or hand them off to your AI coding agent for richer fixes.
* **Customisable rules** – Tweak review preferences and scope to match the D’Agriventory coding standards.

> Source: [CodeRabbit VS Code Marketplace](https://marketplace.visualstudio.com/items?itemName=CodeRabbit.coderabbit-vscode)

---

## Installation (VS Code or Cursor)

1. Open the Extensions view (**Ctrl + Shift + X**).
2. Search for **“CodeRabbit”**.
3. Click **Install** on *CodeRabbit – AI code reviews for VS Code*.
4. Reload the editor when prompted.

The extension icon 🐰 now appears in the Activity Bar.

### Alternative CLI Installation
```bash
code --install-extension coderabbit.coderabbit-vscode
```

---

## Quick Start

1. **Stage or commit** the changes you want reviewed.
2. Click the **CodeRabbit** icon (or run *CodeRabbit: Start Review* from the Command Palette).
3. Wait a few seconds; inline comments will appear next to problematic lines.
4. Hover a comment and choose **Apply Suggested Change** to accept.
5. For complex fixes, click **Fix with AI** – the prompt is copied to your clipboard; paste it into Cursor’s or Github Agent and iterate.

> See the full walkthrough: [egghead.io – Local AI Code Reviews with the CodeRabbit Extension in Cursor](https://egghead.io/local-ai-code-reviews-with-the-code-rabbit-extension-in-cursor~d5472)

---

## Extension Settings

Open **CodeRabbit → ⚙ Settings** or search *CodeRabbit: Settings* in the Command Palette.

| Setting | Purpose | Recommended Value |
|---------|---------|-------------------|
| **Agent Type** | Defines how *Fix with AI* interacts with your editor’s AI agent. | `Native` (Cursor) |
| **Auto Review Mode** | Trigger behaviour after each commit. | `Prompt` (ask first) |
| **Git Platforms** | Link GitHub / GitLab tokens for PR reviews. | Optional |

Detailed docs: [CodeRabbit – Configure VS Code Extension](https://docs.coderabbit.ai/guides/config-vscode/).

---

## Best Practices for D’Agriventory

1. **Review before push** – Run CodeRabbit locally to keep PRs smaller and cleaner.
2. **Pair with PHPUnit + Pest** – After applying fixes, run `composer test` to ensure behaviour is intact.
3. **Respect Coding Standards** – Use CodeRabbit to flag PSR-12 deviations alongside `./vendor/bin/pint`.
4. **Limit scope** – Configure *matchFiles* (see `.contrib-locs` style) to ignore compiled assets and migrations.
5. **Security checks** – Let CodeRabbit highlight potential SQL injection or mass assignment issues before QA.

---

## Troubleshooting

| Symptom | Resolution |
|---------|------------|
| *Extension stalls at “Preparing review”* | Ensure you’re connected to the internet and have a valid CodeRabbit account. |
| *No comments appear* | Confirm you’ve staged/committed files; Auto Review Mode may be `Disabled`. |
| *Fix with AI opens clipboard only* | Switch **Agent Type** to `Native` or install a supported CLI (Claude Code, Codex CLI, etc.). |
| *Cursor Agent can’t apply fix* | Paste the prompt manually or disable experimental Redo fix toggle in Cursor. |

---

## Removal

If you wish to uninstall:
```bash
code --uninstall-extension coderabbit.coderabbit-vscode
```

---

### Further Reading

* Product docs: <https://docs.coderabbit.ai/>
* Best practices guide: <https://docs.coderabbit.ai/best-practices/>
* Configure extension: <https://docs.coderabbit.ai/guides/config-vscode/> 
