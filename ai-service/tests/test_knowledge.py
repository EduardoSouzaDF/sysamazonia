from pathlib import Path
from unittest.mock import patch

import pytest

from app.knowledge import approved_knowledge_context


def test_empty_knowledge_directory_produces_no_context(tmp_path: Path):
    with patch("app.knowledge.APPROVED_DOCUMENTS_DIRECTORY", tmp_path):
        assert approved_knowledge_context(None, 1000) == ""


def test_documents_require_an_institutional_version(tmp_path: Path):
    (tmp_path / "diretriz.md").write_text("Conteúdo aprovado", encoding="utf-8")
    with patch("app.knowledge.APPROVED_DOCUMENTS_DIRECTORY", tmp_path), pytest.raises(ValueError):
        approved_knowledge_context(None, 1000)


def test_only_approved_text_formats_are_loaded(tmp_path: Path):
    (tmp_path / "diretriz.md").write_text("Conteúdo aprovado", encoding="utf-8")
    (tmp_path / "ignorar.pdf").write_bytes(b"nao carregar")
    with patch("app.knowledge.APPROVED_DOCUMENTS_DIRECTORY", tmp_path):
        context = approved_knowledge_context("2026-01", 1000)
    assert "Conteúdo aprovado" in context
    assert "ignorar.pdf" not in context
