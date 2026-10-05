// ==============================================================================
// MitraNet Network OS - Hardware Package JS
// SFP/SFP+ Optical Transceivers, Thermal & Health Sensors, ONLP Whitebox Platform
// ==============================================================================

async function loadSFP() {
  const iface = document.getElementById('sfp-iface-input')?.value || 'eth0';
  const data = await apiFetch(`/api/sfp?iface=${encodeURIComponent(iface)}`);
  const box = document.getElementById('raw-sfp-box');
  if (!box || !data) return;

  let text = `=== SFP Diagnostics for ${data.interface} ===\n`;
  text += (data.diagnostic && Array.isArray(data.diagnostic)) ? data.diagnostic.join('\n') : JSON.stringify(data, null, 2);
  box.textContent = text;
}

async function loadSensors() {
  const data = await apiFetch('/api/hardware/sensors');
  const box = document.getElementById('raw-sensors-box');
  if (!box || !data) return;

  let text = '=== Hardware Thermal & Health Sensors ===\n';
  if (data.sensors) text += data.sensors.join('\n') + '\n';
  if (data.fans) text += '\n--- Fans ---\n' + data.fans.join('\n') + '\n';
  if (data.psu) text += '\n--- Power Supplies ---\n' + data.psu.join('\n') + '\n';
  text += `\nFan Policy: ${data.fan_control || 'Auto'}\nCPU Governor: ${data.governor || 'performance'}`;
  box.textContent = text;
}

async function loadPlatformInfo() {
  const data = await apiFetch('/api/mitranet/platform');
  const box = document.getElementById('raw-platform-box');
  if (!box || !data) return;
  box.textContent = JSON.stringify(data, null, 2);
}

async function syncMitranetConfig() {
  const res = await apiFetch('/api/mitranet/apply', { method: 'POST', body: JSON.stringify({}) });
  if (res && res.status === 'success') {
    showAlert('MitraNet platform configuration synchronized!', 'success');
  } else {
    showAlert('Failed to synchronize configuration: ' + (res?.message || 'Error'), 'error');
  }
}
