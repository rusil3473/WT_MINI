const state = {
  token: localStorage.getItem('todo_token') || '',
  user: null,
  todos: [],
  filter: 'all'
};

const authView = document.getElementById('authView');
const appView = document.getElementById('appView');
const authMessage = document.getElementById('authMessage');
const todoMessage = document.getElementById('todoMessage');
const welcomeText = document.getElementById('welcomeText');
const todoList = document.getElementById('todoList');

const statTotal = document.getElementById('statTotal');
const statPending = document.getElementById('statPending');
const statCompleted = document.getElementById('statCompleted');
const statPercent = document.getElementById('statPercent');

function setMessage(el, text, kind = '') {
  el.textContent = text;
  el.className = `message ${kind}`.trim();
}

async function api(path, options = {}) {
  const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };
  if (state.token) headers.Authorization = `Bearer ${state.token}`;

  const res = await fetch(path, { ...options, headers });
  const hasJson = res.headers.get('content-type')?.includes('application/json');
  const body = hasJson ? await res.json() : {};

  if (!res.ok) throw new Error(body.error || 'Request failed');
  return body;
}

function switchAuthTab(mode) {
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const tabLogin = document.getElementById('tabLogin');
  const tabRegister = document.getElementById('tabRegister');

  const login = mode === 'login';
  loginForm.classList.toggle('active', login);
  registerForm.classList.toggle('active', !login);
  tabLogin.classList.toggle('active', login);
  tabRegister.classList.toggle('active', !login);
  setMessage(authMessage, '');
}

function filteredTodos() {
  if (state.filter === 'active') return state.todos.filter((t) => !t.completed);
  if (state.filter === 'completed') return state.todos.filter((t) => t.completed);
  return state.todos;
}

function renderStats() {
  const total = state.todos.length;
  const completed = state.todos.filter((t) => t.completed).length;
  const pending = total - completed;
  const percent = total === 0 ? 0 : Math.round((completed / total) * 100);

  statTotal.textContent = String(total);
  statPending.textContent = String(pending);
  statCompleted.textContent = String(completed);
  statPercent.textContent = `${percent}%`;
}

function renderTodos() {
  const data = filteredTodos();
  todoList.innerHTML = '';
  renderStats();

  if (data.length === 0) {
    setMessage(todoMessage, 'No task in this view.');
    return;
  }

  setMessage(todoMessage, `${data.length} task(s)`);

  for (const todo of data) {
    const li = document.createElement('li');
    li.className = `todo-item ${todo.completed ? 'completed' : ''}`;

    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = !!todo.completed;
    checkbox.addEventListener('change', () => updateTodo(todo.id, { completed: checkbox.checked }));

    const content = document.createElement('div');
    const title = document.createElement('p');
    title.className = 'todo-title';
    title.textContent = todo.title;

    const desc = document.createElement('p');
    desc.className = 'todo-desc';
    desc.textContent = todo.description || 'No description';

    const meta = document.createElement('div');
    meta.className = 'todo-meta';

    const prioritySelect = document.createElement('select');
    prioritySelect.innerHTML = `
      <option value="low">Low</option>
      <option value="medium">Medium</option>
      <option value="high">High</option>
    `;
    prioritySelect.value = todo.priority || 'medium';
    prioritySelect.className = `badge priority-${prioritySelect.value}`;
    prioritySelect.addEventListener('change', () => updateTodo(todo.id, { priority: prioritySelect.value }));

    const dueDateInput = document.createElement('input');
    dueDateInput.type = 'date';
    dueDateInput.value = todo.dueDate || '';
    dueDateInput.className = 'badge';
    dueDateInput.addEventListener('change', () => {
      const value = dueDateInput.value || null;
      updateTodo(todo.id, { dueDate: value });
    });

    meta.appendChild(prioritySelect);
    meta.appendChild(dueDateInput);

    content.appendChild(title);
    content.appendChild(desc);
    content.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'todo-actions';

    const editBtn = document.createElement('button');
    editBtn.textContent = 'Edit';
    editBtn.addEventListener('click', async () => {
      const nextTitle = prompt('Edit title', todo.title);
      if (nextTitle === null) return;
      const nextDescription = prompt('Edit description', todo.description || '');
      if (nextDescription === null) return;
      await updateTodo(todo.id, {
        title: nextTitle.trim(),
        description: nextDescription.trim()
      });
    });

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.addEventListener('click', () => deleteTodo(todo.id));

    actions.appendChild(editBtn);
    actions.appendChild(deleteBtn);

    li.appendChild(checkbox);
    li.appendChild(content);
    li.appendChild(actions);
    todoList.appendChild(li);
  }
}

async function fetchMe() {
  const res = await api('/api/auth/me');
  state.user = res.data;
  welcomeText.textContent = `Welcome, ${state.user.name}`;
}

async function fetchTodos() {
  const res = await api('/api/todos');
  state.todos = res.data;
  renderTodos();
}

async function addTodo(event) {
  event.preventDefault();
  const titleEl = document.getElementById('todoTitle');
  const descEl = document.getElementById('todoDescription');
  const priorityEl = document.getElementById('todoPriority');
  const dueDateEl = document.getElementById('todoDueDate');

  try {
    await api('/api/todos', {
      method: 'POST',
      body: JSON.stringify({
        title: titleEl.value.trim(),
        description: descEl.value.trim(),
        priority: priorityEl.value,
        dueDate: dueDateEl.value || null
      })
    });

    titleEl.value = '';
    descEl.value = '';
    priorityEl.value = 'medium';
    dueDateEl.value = '';
    await fetchTodos();
  } catch (error) {
    setMessage(todoMessage, error.message, 'error');
  }
}

async function updateTodo(id, payload) {
  try {
    await api(`/api/todos/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload)
    });
    await fetchTodos();
  } catch (error) {
    setMessage(todoMessage, error.message, 'error');
  }
}

async function deleteTodo(id) {
  try {
    await api(`/api/todos/${id}`, { method: 'DELETE' });
    await fetchTodos();
  } catch (error) {
    setMessage(todoMessage, error.message, 'error');
  }
}

function showApp() {
  authView.classList.add('hidden');
  appView.classList.remove('hidden');
  location.hash = 'dashboard';
}

function showAuth() {
  appView.classList.add('hidden');
  authView.classList.remove('hidden');
  location.hash = 'auth';
}

function saveToken(token) {
  state.token = token;
  localStorage.setItem('todo_token', token);
}

function clearSession() {
  state.token = '';
  state.user = null;
  state.todos = [];
  localStorage.removeItem('todo_token');
}

async function handleLogin(event) {
  event.preventDefault();
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;

  try {
    const res = await api('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });

    saveToken(res.data.token);
    await startApp();
    setMessage(authMessage, 'Login success.', 'success');
  } catch (error) {
    setMessage(authMessage, error.message, 'error');
  }
}

async function handleRegister(event) {
  event.preventDefault();
  const name = document.getElementById('registerName').value.trim();
  const email = document.getElementById('registerEmail').value.trim();
  const password = document.getElementById('registerPassword').value;

  try {
    const res = await api('/api/auth/register', {
      method: 'POST',
      body: JSON.stringify({ name, email, password })
    });

    saveToken(res.data.token);
    await startApp();
    setMessage(authMessage, 'Register success.', 'success');
  } catch (error) {
    setMessage(authMessage, error.message, 'error');
  }
}

async function startApp() {
  try {
    await fetchMe();
    await fetchTodos();
    showApp();
  } catch {
    clearSession();
    showAuth();
  }
}

function bindEvents() {
  document.getElementById('tabLogin').addEventListener('click', () => switchAuthTab('login'));
  document.getElementById('tabRegister').addEventListener('click', () => switchAuthTab('register'));
  document.getElementById('loginForm').addEventListener('submit', handleLogin);
  document.getElementById('registerForm').addEventListener('submit', handleRegister);
  document.getElementById('todoForm').addEventListener('submit', addTodo);
  document.getElementById('logoutBtn').addEventListener('click', () => {
    clearSession();
    switchAuthTab('login');
    showAuth();
  });

  document.querySelectorAll('.filter').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('.filter').forEach((b) => b.classList.remove('active'));
      button.classList.add('active');
      state.filter = button.dataset.filter;
      renderTodos();
    });
  });
}

bindEvents();
switchAuthTab('login');
if (state.token) {
  startApp();
}
