from pathlib import Path

APPROVED_DOCUMENTS_DIRECTORY = Path(__file__).parent / "documents"


def approved_document_paths() -> list[Path]:
    """Retorna apenas documentos inseridos deliberadamente no diretório institucional controlado."""
    return sorted(path for path in APPROVED_DOCUMENTS_DIRECTORY.iterdir() if path.is_file() and path.name != ".gitkeep")
