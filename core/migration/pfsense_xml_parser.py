"""
pfSense config.xml DOM/AST Parser.
Reads XML into structured Python dictionaries without manual string scraping.
"""

import xml.etree.ElementTree as ET
from typing import Dict, Any, Optional


class PfSenseXmlParser:
    @classmethod
    def parse_string(cls, xml_text: str) -> Dict[str, Any]:
        root = ET.fromstring(xml_text)
        return {root.tag: cls._element_to_dict(root)}

    @classmethod
    def parse_file(cls, filepath: str) -> Dict[str, Any]:
        tree = ET.parse(filepath)
        root = tree.getroot()
        return {root.tag: cls._element_to_dict(root)}

    @classmethod
    def _element_to_dict(cls, elem: ET.Element) -> Any:
        children = list(elem)
        if not children:
            text = elem.text
            return text.strip() if text else ""

        result: Dict[str, Any] = {}
        for child in children:
            child_dict = cls._element_to_dict(child)
            tag = child.tag
            if tag in result:
                # Convert duplicate tags into lists (e.g. multiple <rule>, <alias>, <item>)
                if isinstance(result[tag], list):
                    result[tag].append(child_dict)
                else:
                    result[tag] = [result[tag], child_dict]
            else:
                result[tag] = child_dict
        return result
