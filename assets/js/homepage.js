document.getElementById('createBtn').addEventListener('click', () => {
  const target = document.getElementById('target').value.trim();
  if (!target) return alert('Masukkan URL tujuan');

  let id = document.getElementById('customId').value.trim() || Math.random().toString(36).substring(2, 10);
  id = id.replace(/[^a-zA-Z0-9_-]/g, '');

  // Ganti domain di config.php nanti
  const domain = window.location.origin;
  const link = `${domain}/track.php?id=${id}&r=${encodeURIComponent(target)}`;
  const live = `${domain}/liveview.php?id=${id}`;

  document.getElementById('outLink').value = link;
  document.getElementById('liveLink').href = live;
  document.getElementById('result').classList.remove('hidden');
});

document.getElementById('copyBtn').addEventListener('click', () => {
  const inp = document.getElementById('outLink');
  inp.select();
  document.execCommand('copy');
  alert('Copied');
});
