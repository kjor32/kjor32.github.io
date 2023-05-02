const showProjectsBtn = document.getElementById('show-projects-btn');
const projectsContainer = document.getElementById('projects-container');

showProjectsBtn.addEventListener('click', () => {
  projectsContainer.style.display = 'block'; // показываем контейнер с проектами
});

const showContactsBtn = document.getElementById('show-contacts-btn');
const contactsContainer = document.getElementById('contacts-container');

showContactsBtn.addEventListener('click', () => {
  contactsContainer.style.display = 'block'; // показываем контейнер с контактами
});

