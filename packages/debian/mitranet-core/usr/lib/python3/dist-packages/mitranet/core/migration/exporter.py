"""
MitraNet Migration Exporter.
Coordinates parsing, mapping, schema/semantic validation, and disk export of JSON and reports.
"""

import os
from typing import Tuple
from mitranet.core.config.model import MitraNetConfig
from mitranet.core.config.loader import ConfigWriter
from mitranet.core.config.validator import ConfigValidator
from mitranet.core.migration.pfsense_xml_parser import PfSenseXmlParser
from mitranet.core.migration.pfsense_mapper import PfSenseMapper
from mitranet.core.migration.compatibility import InterfaceMapper
from mitranet.core.migration.migration_report import MigrationReport


class MigrationExporter:
    @classmethod
    def migrate_pfsense_file(
        cls,
        xml_filepath: str,
        output_json_path: str,
        output_report_json_path: str,
        output_report_md_path: str,
        iface_mapper: InterfaceMapper = None
    ) -> Tuple[MitraNetConfig, MigrationReport]:
        # 1. Parse XML
        raw_dict = PfSenseXmlParser.parse_file(xml_filepath)

        # 2. Map to MitraNet Canonical Model
        mapper = PfSenseMapper(iface_mapper=iface_mapper)
        cfg, report = mapper.map_to_mitranet(raw_dict)

        # 3. Validate Semantic Constraints
        errors = ConfigValidator.validate(cfg)
        for err in errors:
            report.errors.append(err)

        # 4. Export JSON Configuration
        ConfigWriter.write_to_file(cfg, output_json_path)

        # 5. Export Migration Reports
        os.makedirs(os.path.dirname(os.path.abspath(output_report_json_path)), exist_ok=True)
        with open(output_report_json_path, "w", encoding="utf-8") as f:
            f.write(report.model_dump_json(indent=2))

        with open(output_report_md_path, "w", encoding="utf-8") as f:
            f.write(report.generate_markdown())

        return cfg, report
