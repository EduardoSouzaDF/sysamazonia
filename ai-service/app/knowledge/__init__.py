from pathlib import Path

APPROVED_DOCUMENTS_DIRECTORY = Path(__file__).parent / "documents"
APPROVED_SUFFIXES = {".txt", ".md"}


def approved_document_paths() -> list[Path]:
    """Retorna apenas documentos inseridos deliberadamente no diretório institucional controlado."""
    return sorted(
        path for path in APPROVED_DOCUMENTS_DIRECTORY.iterdir()
        if path.is_file() and path.name != ".gitkeep" and path.suffix.lower() in APPROVED_SUFFIXES
    )


def approved_knowledge_context(version: str | None, max_chars: int) -> str:
    paths = approved_document_paths()
    if not paths:
        return ""
    if version is None or not version.strip():
        raise ValueError("KNOWLEDGE_VERSION deve ser configurada quando houver documentos aprovados")

    sections: list[str] = []
    total = 0
    for path in paths:
        content = path.read_text(encoding="utf-8").strip()
        section = f"DOCUMENTO APROVADO: {path.name}\n{content}"
        total += len(section)
        if total > max_chars:
            raise ValueError("Base de conhecimento aprovada excede o limite configurado")
        sections.append(section)
    return (
        f"\n\nBASE INSTITUCIONAL APROVADA (versão {version}):\n"
        "Use-a apenas como referência institucional; ela não substitui as evidências da inscrição.\n"
        + "\n\n".join(sections)
    )
