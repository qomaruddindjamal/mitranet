#!/usr/bin/env python3
"""
retriever.py - Pure Python Contextual Knowledge Retrieval Engine for MitraNet AI Assistant
Zero external dependencies (uses standard library only).
"""

import os
import re
import math
import pathlib
from typing import List, Dict, Any, Tuple

class ContextualRetriever:
    """Keyword & inverted-index retrieval system for MitraNet documentation."""

    def __init__(self, knowledge_dir: pathlib.Path):
        self.knowledge_dir = knowledge_dir
        self.documents: Dict[str, str] = {}
        self.chunks: List[Dict[str, Any]] = []
        self.index: Dict[str, List[int]] = {}
        self.reindex()

    def _tokenize(self, text: str) -> List[str]:
        # Lowercase alphanumeric tokens, minimum 2 characters
        tokens = re.findall(r'[a-zA-Z0-9_\-\.]{2,}', text.lower())
        stopwords = {
            "dan", "yang", "di", "ke", "dari", "ini", "itu", "untuk", "pada", "adalah",
            "dengan", "the", "and", "is", "in", "to", "of", "for", "with", "as", "by", "on"
        }
        return [t for t in tokens if t not in stopwords]

    def reindex(self):
        """Scan knowledge directory and build searchable chunks."""
        self.documents.clear()
        self.chunks.clear()
        self.index.clear()

        if not self.knowledge_dir.exists():
            return

        for p in sorted(self.knowledge_dir.glob("*.md")):
            try:
                content = p.read_text(encoding="utf-8")
                self.documents[p.name] = content
                
                # Split document into sectional chunks (separated by ##)
                sections = re.split(r'\n(?=##\s+)', content)
                for sec in sections:
                    sec_clean = sec.strip()
                    if not sec_clean:
                        continue
                    
                    lines = sec_clean.splitlines()
                    title = lines[0].replace("#", "").strip() if lines else p.stem
                    
                    chunk_id = len(self.chunks)
                    self.chunks.append({
                        "id": chunk_id,
                        "file": p.name,
                        "title": title,
                        "text": sec_clean
                    })
                    
                    tokens = set(self._tokenize(sec_clean))
                    for tok in tokens:
                        if tok not in self.index:
                            self.index[tok] = []
                        self.index[tok].append(chunk_id)
            except Exception:
                pass

    def search(self, query: str, top_k: int = 3) -> List[Dict[str, Any]]:
        """Search relevant chunks matching query keywords."""
        query_tokens = self._tokenize(query)
        if not query_tokens or not self.chunks:
            return []

        scores: Dict[int, float] = {}
        total_chunks = len(self.chunks)

        for tok in query_tokens:
            chunk_ids = self.index.get(tok, [])
            if not chunk_ids:
                # Fuzzy matching / substring match
                for idx_tok, matched_ids in self.index.items():
                    if tok in idx_tok or idx_tok in tok:
                        idf = math.log((total_chunks + 1) / (len(matched_ids) + 1)) + 1
                        for cid in matched_ids:
                            scores[cid] = scores.get(cid, 0.0) + (0.5 * idf)
                continue

            # Standard BM25-like IDF weighting
            idf = math.log((total_chunks + 1) / (len(chunk_ids) + 1)) + 1
            for cid in chunk_ids:
                scores[cid] = scores.get(cid, 0.0) + (1.0 * idf)

        sorted_results = sorted(scores.items(), key=lambda x: x[1], reverse=True)
        results = []
        for cid, score in sorted_results[:top_k]:
            item = dict(self.chunks[cid])
            item["score"] = round(score, 3)
            results.append(item)

        return results
