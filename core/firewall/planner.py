"""
MitraNet Firewall Differential Planner.
Phase 3A: Calculates diff between running and candidate firewall states,
producing deterministic, ordered differential operations (ADD_RULE, DELETE_RULE,
UPDATE_RULE, SET_POLICY, SET_ZONE).
"""

from typing import List, Dict, Any, Optional
from pydantic import BaseModel, Field
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallZone,
    FirewallPolicy,
)


class FirewallOperationType:
    ADD_RULE = "ADD_RULE"
    DELETE_RULE = "DELETE_RULE"
    UPDATE_RULE = "UPDATE_RULE"
    CREATE_ZONE = "CREATE_ZONE"
    DELETE_ZONE = "DELETE_ZONE"
    UPDATE_ZONE = "UPDATE_ZONE"
    SET_POLICY = "SET_POLICY"


class FirewallOperation(BaseModel):
    """An individual atomic or step-wise firewall operation in an execution plan."""
    op_id: str
    op_type: str
    target: str
    params: Dict[str, Any] = Field(default_factory=dict)
    inverse_op: Optional[str] = None
    inverse_params: Dict[str, Any] = Field(default_factory=dict)


class FirewallPlanner:
    """Computes differential execution plans between running and candidate firewall configs."""

    @classmethod
    def generate_plan(
        cls,
        candidate: FirewallTableConfig,
        running: Optional[FirewallTableConfig] = None,
    ) -> List[FirewallOperation]:
        """
        Produces an ordered list of forward operations and their exact inverses
        for precise change preview, apply, and rollback.
        """
        operations: List[FirewallOperation] = []
        op_idx = 1

        running_rules: Dict[str, FirewallRule] = (
            {r.id: r for r in running.rules} if running else {}
        )
        running_zones: Dict[str, FirewallZone] = (
            running.zones if running else {}
        )
        running_policy: Optional[FirewallPolicy] = (
            running.policy if running else None
        )

        candidate_rules: Dict[str, FirewallRule] = {r.id: r for r in candidate.rules}

        # 1. Policy Changes
        if running_policy is None or running_policy != candidate.policy:
            operations.append(
                FirewallOperation(
                    op_id=f"fw_op_{op_idx:04d}",
                    op_type=FirewallOperationType.SET_POLICY,
                    target="policy",
                    params={"policy": candidate.policy.model_dump()},
                    inverse_op=FirewallOperationType.SET_POLICY,
                    inverse_params={"policy": running_policy.model_dump() if running_policy else {}},
                )
            )
            op_idx += 1

        # 2. Zone Deletions
        for z_name, z_val in running_zones.items():
            if z_name not in candidate.zones:
                operations.append(
                    FirewallOperation(
                        op_id=f"fw_op_{op_idx:04d}",
                        op_type=FirewallOperationType.DELETE_ZONE,
                        target=z_name,
                        params={"zone": z_name},
                        inverse_op=FirewallOperationType.CREATE_ZONE,
                        inverse_params={"zone": z_val.model_dump()},
                    )
                )
                op_idx += 1

        # 3. Zone Additions and Updates
        for z_name, z_val in candidate.zones.items():
            if z_name not in running_zones:
                operations.append(
                    FirewallOperation(
                        op_id=f"fw_op_{op_idx:04d}",
                        op_type=FirewallOperationType.CREATE_ZONE,
                        target=z_name,
                        params={"zone": z_val.model_dump()},
                        inverse_op=FirewallOperationType.DELETE_ZONE,
                        inverse_params={"zone": z_name},
                    )
                )
                op_idx += 1
            elif running_zones[z_name] != z_val:
                operations.append(
                    FirewallOperation(
                        op_id=f"fw_op_{op_idx:04d}",
                        op_type=FirewallOperationType.UPDATE_ZONE,
                        target=z_name,
                        params={"zone": z_val.model_dump()},
                        inverse_op=FirewallOperationType.UPDATE_ZONE,
                        inverse_params={"zone": running_zones[z_name].model_dump()},
                    )
                )
                op_idx += 1

        # 4. Rule Deletions (rules in running but absent in candidate)
        for r_id, r_val in running_rules.items():
            if r_id not in candidate_rules:
                operations.append(
                    FirewallOperation(
                        op_id=f"fw_op_{op_idx:04d}",
                        op_type=FirewallOperationType.DELETE_RULE,
                        target=r_id,
                        params={"rule_id": r_id},
                        inverse_op=FirewallOperationType.ADD_RULE,
                        inverse_params={"rule": r_val.model_dump()},
                    )
                )
                op_idx += 1

        # 5. Rule Additions and Updates (ordered by priority)
        sorted_candidate_rules = sorted(candidate.rules, key=lambda x: x.priority)
        for rule in sorted_candidate_rules:
            if rule.id not in running_rules:
                operations.append(
                    FirewallOperation(
                        op_id=f"fw_op_{op_idx:04d}",
                        op_type=FirewallOperationType.ADD_RULE,
                        target=rule.id,
                        params={"rule": rule.model_dump()},
                        inverse_op=FirewallOperationType.DELETE_RULE,
                        inverse_params={"rule_id": rule.id},
                    )
                )
                op_idx += 1
            elif running_rules[rule.id] != rule:
                operations.append(
                    FirewallOperation(
                        op_id=f"fw_op_{op_idx:04d}",
                        op_type=FirewallOperationType.UPDATE_RULE,
                        target=rule.id,
                        params={"rule": rule.model_dump()},
                        inverse_op=FirewallOperationType.UPDATE_RULE,
                        inverse_params={"rule": running_rules[rule.id].model_dump()},
                    )
                )
                op_idx += 1

        return operations
