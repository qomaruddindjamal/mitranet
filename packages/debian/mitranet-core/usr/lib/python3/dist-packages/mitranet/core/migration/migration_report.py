"""
Migration Record Status and Tracking Models.
"""

from typing import List, Dict, Any, Optional
from pydantic import BaseModel, Field


class MigrationItem(BaseModel):
    category: str
    feature: str
    status: str = Field(..., description="MIGRATED, PARTIAL, UNSUPPORTED, FAILED, SKIPPED")
    source_detail: str = Field(default="")
    target_detail: str = Field(default="")
    reason: Optional[str] = Field(default=None)


class MigrationReport(BaseModel):
    source_platform: str = "pfSense"
    source_format: str = "config.xml"
    source_version: Optional[str] = None
    target_platform: str = "MitraNet"
    target_format: str = "JSON"
    target_schema_version: str = "1.0"
    summary: Dict[str, int] = Field(default_factory=dict)
    items: List[MigrationItem] = Field(default_factory=list)
    warnings: List[str] = Field(default_factory=list)
    errors: List[str] = Field(default_factory=list)
    unsupported_features: List[Dict[str, str]] = Field(default_factory=list)

    def add_item(self, item: MigrationItem):
        self.items.append(item)
        status_key = item.status.lower()
        self.summary[status_key] = self.summary.get(status_key, 0) + 1
        if item.status == "UNSUPPORTED":
            self.unsupported_features.append({
                "feature": item.feature,
                "reason": item.reason or "Feature not available in Linux or MitraNet core"
            })
        elif item.status == "PARTIAL":
            self.warnings.append(f"Partial migration on {item.feature}: {item.reason}")

    def generate_markdown(self) -> str:
        lines = [
            "# MitraNet Migration Report",
            f"- **Source**: {self.source_platform} ({self.source_format}, v{self.source_version or 'unknown'})",
            f"- **Target**: {self.target_platform} ({self.target_format}, schema v{self.target_schema_version})",
            "",
            "## Summary",
            f"- **Migrated**: {self.summary.get('migrated', 0)}",
            f"- **Partial**: {self.summary.get('partial', 0)}",
            f"- **Unsupported**: {self.summary.get('unsupported', 0)}",
            f"- **Failed**: {self.summary.get('failed', 0)}",
            "",
            "## Migration Item Details",
            "| Category | Feature | Status | Reason / Details |",
            "| :--- | :--- | :--- | :--- |",
        ]
        for item in self.items:
            reason = item.reason or item.target_detail or ""
            lines.append(f"| {item.category} | {item.feature} | **{item.status}** | {reason} |")

        if self.warnings:
            lines.extend(["", "## Warnings"])
            for w in self.warnings:
                lines.append(f"- {w}")

        if self.unsupported_features:
            lines.extend(["", "## Unsupported Items (Retained in Metadata)"])
            for u in self.unsupported_features:
                lines.append(f"- **{u['feature']}**: {u['reason']}")

        return "\n".join(lines)
