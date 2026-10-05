// ==============================================================================
// MitraNet Network OS - QoS Package JS
// Bandwidth & Smart Queue Management (CAKE SQM)
// ==============================================================================

async function applyQoS(action) {
  const wan = document.getElementById('wan-if-select')?.value || 'auto';
  let downRate = document.getElementById('qos-down-rate')?.value || 'unlimited';
  let upRate = document.getElementById('qos-up-rate')?.value || 'unlimited';

  if (action === 'stop' || action === 'unlimited') {
    downRate = 'unlimited';
    upRate = 'unlimited';
    if (document.getElementById('qos-down-rate')) document.getElementById('qos-down-rate').value = 'unlimited';
    if (document.getElementById('qos-up-rate')) document.getElementById('qos-up-rate').value = 'unlimited';
  }

  const res = await apiFetch('/api/qos/apply', {
    method: 'POST',
    body: JSON.stringify({ action, interface: wan, down_rate: downRate, up_rate: upRate })
  });

  if (res && res.status === 'success') {
    showAlert(res.message, 'success');
  } else {
    showAlert('Failed to apply QoS policy: ' + (res?.message || 'Error'), 'error');
  }
}
