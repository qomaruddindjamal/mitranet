# MITRANET WEBUI MANUAL QA & AUDIT GUIDE

## 1. Access Credentials
- **URL:** `http://127.0.0.1:8443/`
- **Default Username:** `admin`
- **Default Password:** `mitranet`

## 2. Manual Verification Checklist
1. **Login Flow:**
   - Open browser to `http://127.0.0.1:8443/`.
   - Verify login modal is rendered with brand title and credential inputs.
   - Enter invalid password; confirm error notification displays.
   - Enter valid credentials (`admin` / `mitranet`); confirm automatic redirect to Dashboard.

2. **Dashboard:**
   - Verify real hardware information: Hostname, Linux kernel release, architecture, CPU load, and RAM usage.
   - Check real interface summary counters and default gateway status.

3. **Interfaces:**
   - Verify discovered interfaces (e.g., `lo`, `enp0s3`, `enp0s8`).
   - Check MTU, MAC, and assigned IPv4/IPv6 addresses.
   - Test safe administrative toggle (`up` / `down`) on test interface (`enp0s8`).

4. **Routing:**
   - Verify table displaying kernel IPv4 and IPv6 routes.
   - Confirm default gateway route (`0.0.0.0/0`) egressing via `enp0s3`.

5. **Firewall (nftables):**
   - Verify base chain policies (`input`, `forward`, `output`).
   - Add a test rule in the Candidate tab.
   - Verify rule appears in Candidate ruleset.
   - Apply or discard candidate rules.

6. **Configuration & Transactions:**
   - Inspect candidate and running version numbers.
   - Verify lock status is false when idle.
   - Test rollback functionality if changes have been made.

7. **System Logs:**
   - Filter logs by category: `system`, `firewall`, `gateway`, `transaction`.
   - Confirm real logs stream from the appliance kernel and journal.

8. **Logout:**
   - Click the Logout button in the sidebar footer.
   - Confirm session termination and return to the login screen.
