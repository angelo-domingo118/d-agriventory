# Contributing to Documentation

Guidelines for contributing to D'Agriventory documentation to maintain consistency and quality.

## suggesting-documentation-changes

### before-you-start

- **Read existing docs**: Familiarise yourself with current documentation structure and tone
- **Check issues**: See if your suggestion already exists in [GitHub Issues](../../issues)
- **Small changes**: Use GitHub's web editor for typo fixes and minor updates
- **Major changes**: Follow the full contribution process below

### contribution-process

1. **Fork the repository** and create a documentation branch
2. **Make your changes** following the style guidelines below  
3. **Test locally** using a markdown previewer
4. **Submit a pull request** with clear description of changes
5. **Respond to feedback** during the review process

## branch-naming-conventions

Use the `docs/` prefix followed by the topic or file being changed:

```bash
# Good branch names
docs/installation-guide
docs/api-reference  
docs/troubleshooting-section
docs/coding-standards-update
docs/faq-additions

# Bad branch names
update-docs
fix-readme
documentation
new-guide
```

### multiple-files

For changes spanning multiple documentation files:

```bash
docs/complete-overhaul
docs/structure-reorganisation
docs/style-consistency
```

## markdown-standards

### file-structure

Every documentation file must follow this structure:

```markdown
# File Title

Single-sentence purpose statement explaining what this file covers.

## section-heading

Content using British English, no emoji, no exclamation marks.

### subsection-heading

More specific content.

## further-reading

- [External Link Title](https://example.com)
- [Internal Link](../other-file.md)
```

### heading-conventions

- **Use slug-case**: `## example-heading` not `## Example Heading`
- **Descriptive**: Headings should clearly indicate content
- **Consistent depth**: Don't skip heading levels (h1 → h3)
- **Maximum depth**: Stop at h3 (`###`) for readability

### code-blocks

All code examples must use fenced blocks with language specification:

```markdown
<!-- ✅ Good -->
```bash
php artisan migrate
```

```php
public function example()
{
    return 'properly formatted';
}
```

<!-- ❌ Bad -->
Run `php artisan migrate` to apply migrations.
```

### link-formatting

```markdown
<!-- ✅ Good - Descriptive link text -->
Refer to the [Laravel Eloquent documentation](https://laravel.com/docs/eloquent) for relationship details.

<!-- ❌ Bad - Generic link text -->
Click [here](https://laravel.com/docs/eloquent) for more information.
```

### list-formatting

Use consistent bullet points and proper spacing:

```markdown
## list-example

- **Bold term**: Description with proper capitalisation
- **Another term**: No trailing punctuation for short phrases
- **Longer description**: Use full sentences with proper punctuation.

### numbered-steps

1. **First step**: Clear, actionable instruction
2. **Second step**: Logical progression from previous step  
3. **Final step**: Concluding action or verification
```

## style-guidelines

### tone-and-voice

- **Professional but friendly**: Confidence-building without being casual
- **Clear and concise**: Avoid jargon and unnecessary complexity
- **Actionable**: Focus on what the reader should do
- **British English**: Use "colour" not "color", "realise" not "realize"

### writing-rules

- **No emoji**: Keep content professional 
- **No exclamation marks**: Use period for declarative statements
- **Short paragraphs**: Maximum 4 sentences per paragraph
- **Active voice**: "Run the command" not "The command should be run"
- **Present tense**: "Laravel uses" not "Laravel will use"

### file-limits

- **Maximum 140 lines** per file including headers and whitespace
- **Split long content**: Create `part-2.md` files when exceeding limit
- **Cross-reference**: Link between related files clearly

## quality-checklist

Before submitting documentation changes:

- [ ] **Spell check**: Use British English spelling
- [ ] **Grammar check**: Read aloud to catch awkward phrasing  
- [ ] **Link verification**: Ensure all links work correctly
- [ ] **Code testing**: Verify all code examples execute successfully
- [ ] **Formatting consistency**: Follow markdown standards above
- [ ] **File length**: Confirm under 140 lines
- [ ] **Purpose statement**: First sentence clearly explains file purpose

## review-process

### documentation-reviews

Documentation pull requests are reviewed for:

- **Technical accuracy**: Code examples and procedures work correctly
- **Style compliance**: Follows established guidelines and tone
- **Completeness**: Covers topic thoroughly without gaps
- **Accessibility**: Clear for intended audience skill level

### approval-criteria

- **One reviewer minimum**: Another contributor must approve changes
- **Maintainer approval**: Core team member final approval for major changes
- **CI checks**: Automated markdown linting must pass
- **No breaking changes**: Links and references remain functional

## markdown-linting

The project uses automated markdown linting to ensure consistency:

```bash
# Install markdownlint-cli
npm install -g markdownlint-cli

# Lint all documentation files
markdownlint docs/ *.md

# Lint specific file
markdownlint docs/overview.md
```

### common-linting-rules

- **MD001**: Heading levels increment by one level at a time
- **MD013**: Line length should not exceed 120 characters  
- **MD025**: Multiple top level headings in the same document
- **MD032**: Lists should be surrounded by blank lines
- **MD033**: No inline HTML (use markdown formatting instead)

## recognition

Contributors to documentation are recognised in:

- **Pull request acknowledgments**: Credited in merge commit messages
- **Documentation credits**: Listed in contributor sections where appropriate
- **Community recognition**: Featured in project discussions and releases

Thank you for helping improve D'Agriventory documentation!