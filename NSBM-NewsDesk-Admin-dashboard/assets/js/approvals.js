(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  const ready = cb => (window.NSBM && window.NSBM.api) ? cb() : setTimeout(() => ready(cb), 150);
  const call = (...args) => window.NSBM.api(...args);
  const notify = (message, ok = true) => { try { window.NSBM.toast(message, ok); } catch (e) { alert(message); } };
  let view = 'pending';

  function td(text) { const cell = document.createElement('td'); cell.textContent = text || '—'; return cell; }
  function actionButton(label, className, handler) {
    const b = document.createElement('button');
    b.type = 'button'; b.className = className; b.textContent = label;
    b.addEventListener('click', async () => { b.disabled = true; try { await handler(); } finally { b.disabled = false; } });
    return b;
  }
  function setBadge(count) {
    const badge = $('approvalsBadge');
    if (!badge) return;
    badge.textContent = String(count);
    badge.hidden = !count;
  }
  async function review(student, decision) {
    if (decision === 'reject' && !confirm('Reject ' + student.name + '? They will not be able to sign in.')) return;
    try {
      await call('student_review', 'POST', { id: Number(student.id), decision });
      notify(decision === 'approve' ? student.name + ' approved.' : student.name + ' rejected.');
      await load();
    } catch (e) { notify(e.message, false); }
  }
  function render(students) {
    const body = $('approvalsTable');
    body.replaceChildren();
    $('approvalsEmpty').classList.toggle('hidden', students.length > 0);
    students.forEach(s => {
      const row = document.createElement('tr');
      row.append(
        td(s.name), td(s.student_id), td(s.email),
        td([s.faculty, s.degree].filter(Boolean).join(' • ')),
        td(s.intake), td(String(s.created_at || '').slice(0, 10))
      );
      const actions = document.createElement('td');
      actions.append(actionButton('Approve', 'small-button approve', () => review(s, 'approve')));
      if (view === 'pending') actions.append(actionButton('Reject', 'small-button reject', () => review(s, 'reject')));
      row.append(actions);
      body.append(row);
    });
  }
  async function load() {
    const body = $('approvalsTable');
    if (!body) return;
    try {
      const r = await call('pending_students', 'GET', undefined, { status: view });
      render(r.students || []);
      setBadge(Number(r.pending_count || 0));
    } catch (e) { notify(e.message, false); }
  }
  function init() {
    document.querySelectorAll('[data-approval-view]').forEach(btn => btn.addEventListener('click', () => {
      view = btn.dataset.approvalView;
      document.querySelectorAll('[data-approval-view]').forEach(b => b.classList.toggle('active', b === btn));
      load();
    }));
    document.querySelector('.side-link[data-page="student-approvals"]')?.addEventListener('click', load);
    window.addEventListener('hashchange', () => { if (location.hash === '#student-approvals') load(); });
    if (location.hash === '#student-approvals') load(); else load();
  }
  document.addEventListener('DOMContentLoaded', () => ready(init));
})();