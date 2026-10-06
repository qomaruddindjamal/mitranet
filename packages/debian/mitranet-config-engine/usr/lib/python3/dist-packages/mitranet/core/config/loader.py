"""
MitraNet JSON Configuration Loader and Writer.
Handles disk persistence, pretty-printing, and atomic file replacements.
"""

import json
import os
import shutil
from typing import Dict, Any
from mitranet.core.config.model import MitraNetConfig
from mitranet.core.config.versioning import ConfigVersionManager
from mitranet.core.config.validator import ConfigValidator
from mitranet.core.config.exceptions import ConfigError


class ConfigLoader:
    @classmethod
    def load_from_file(cls, filepath: str) -> MitraNetConfig:
        if not os.path.exists(filepath):
            raise ConfigError(f"Configuration file not found: {filepath}")
        try:
            with open(filepath, "r", encoding="utf-8") as f:
                data = json.load(f)
        except Exception as e:
            raise ConfigError(f"Failed to parse JSON configuration: {e}")

        # Validate schema version
        ConfigVersionManager.validate_version(data)
        upgraded_data = ConfigVersionManager.upgrade_if_needed(data)
        
        cfg = MitraNetConfig.model_validate(upgraded_data)
        ConfigValidator.assert_valid(cfg)
        return cfg

    @classmethod
    def load_from_json(cls, json_str: str) -> MitraNetConfig:
        data = json.loads(json_str)
        ConfigVersionManager.validate_version(data)
        cfg = MitraNetConfig.model_validate(data)
        ConfigValidator.assert_valid(cfg)
        return cfg


class ConfigWriter:
    @classmethod
    def write_to_file(cls, cfg: MitraNetConfig, filepath: str, atomic: bool = True):
        ConfigValidator.assert_valid(cfg)
        os.makedirs(os.path.dirname(os.path.abspath(filepath)), exist_ok=True)
        json_data = cfg.model_dump_json(indent=2)

        if atomic:
            temp_path = f"{filepath}.tmp"
            with open(temp_path, "w", encoding="utf-8") as f:
                f.write(json_data)
            shutil.move(temp_path, filepath)
        else:
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(json_data)

    @classmethod
    def to_json_string(cls, cfg: MitraNetConfig) -> str:
        return cfg.model_dump_json(indent=2)
