document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide) window.lucide.createIcons();
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.main-nav');
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    toggle.setAttribute('aria-label', open ? 'Open navigation' : 'Close navigation');
    nav?.classList.toggle('open', !open);
  });

  const chatbotTrigger = document.querySelector('[data-chatbot-toggle]');
  const chatbotPanel = document.querySelector('[data-chatbot-panel]');
  const chatbotForm = chatbotPanel?.querySelector('[data-chatbot-form]');
  const chatbotMessages = chatbotPanel?.querySelector('[data-chatbot-messages]');
  const chatbotInput = chatbotForm?.elements.namedItem('message');
  const chatbotSend = chatbotForm?.querySelector('button[type="submit"]');

  if (
    chatbotTrigger instanceof HTMLAnchorElement &&
    chatbotPanel instanceof HTMLElement &&
    chatbotForm instanceof HTMLFormElement &&
    chatbotMessages instanceof HTMLElement &&
    chatbotInput instanceof HTMLTextAreaElement &&
    chatbotSend instanceof HTMLButtonElement
  ) {
    const addChatMessage = (role, text, action = null, isError = false) => {
      const message = document.createElement('article');
      message.className = `chatbot-message chatbot-message-${role}${isError ? ' chatbot-message-error' : ''}`;
      const paragraph = document.createElement('p');
      paragraph.textContent = text;
      message.append(paragraph);

      if (action && typeof action.label === 'string' && typeof action.url === 'string') {
        try {
          const actionUrl = new URL(action.url, window.location.href);
          if (actionUrl.origin === window.location.origin) {
            const link = document.createElement('a');
            link.className = 'chatbot-action';
            link.href = actionUrl.href;
            link.textContent = action.label;
            message.append(link);
          }
        } catch {
          // Ignore invalid action links and keep the text response.
        }
      }

      chatbotMessages.append(message);
      chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
      return message;
    };

    chatbotTrigger.addEventListener('click', (event) => {
      event.preventDefault();
      chatbotPanel.hidden = false;
      chatbotTrigger.setAttribute('aria-expanded', 'true');
      chatbotInput.focus();
    });

    chatbotPanel.querySelector('[data-chatbot-close]')?.addEventListener('click', () => {
      chatbotPanel.hidden = true;
      chatbotTrigger.setAttribute('aria-expanded', 'false');
      chatbotTrigger.focus();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !chatbotPanel.hidden) {
        chatbotPanel.hidden = true;
        chatbotTrigger.setAttribute('aria-expanded', 'false');
        chatbotTrigger.focus();
      }
    });

    chatbotForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const messageText = chatbotInput.value.trim();
      if (!messageText) return;

      addChatMessage('customer', messageText);
      const pending = addChatMessage('assistant', 'One moment…');
      chatbotInput.value = '';
      chatbotSend.disabled = true;

      try {
        const body = new URLSearchParams(new FormData(chatbotForm));
        body.set('message', messageText);
        const response = await fetch(chatbotPanel.dataset.chatbotEndpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body,
          credentials: 'same-origin',
        });
        const result = await response.json();
        pending.remove();
        if (!response.ok) {
          addChatMessage('assistant', result.error || 'The assistant could not process that message.', result.action, true);
          return;
        }
        addChatMessage('assistant', result.answer, result.action);
      } catch {
        pending.remove();
        addChatMessage('assistant', 'We could not reach support chat. Please use the Contact page instead.', null, true);
      } finally {
        chatbotSend.disabled = false;
        chatbotInput.focus();
      }
    });
  }

  const clock = document.querySelector('[data-local-clock]');
  if (!clock) return;

  const hourHand = clock.querySelector('[data-clock-hour]');
  const minuteHand = clock.querySelector('[data-clock-minute]');
  const secondHand = clock.querySelector('[data-clock-second]');
  const timeDisplay = clock.querySelector('[data-local-time]');
  const dateDisplay = clock.querySelector('[data-local-date]');
  const zoneDisplay = clock.querySelector('[data-local-zone]');
  if (!hourHand || !minuteHand || !secondHand || !timeDisplay || !dateDisplay || !zoneDisplay) return;

  const updateClock = () => {
    const now = new Date();
    const seconds = now.getSeconds();
    const minutes = now.getMinutes() + seconds / 60;
    const hours = (now.getHours() % 12) + minutes / 60;

    hourHand.style.transform = `translateX(-50%) rotate(${hours * 30}deg)`;
    minuteHand.style.transform = `translateX(-50%) rotate(${minutes * 6}deg)`;
    secondHand.style.transform = `translateX(-50%) rotate(${seconds * 6}deg)`;
    timeDisplay.textContent = now.toLocaleTimeString(undefined, {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
    timeDisplay.dateTime = now.toISOString();
    dateDisplay.textContent = now.toLocaleDateString(undefined, {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
    dateDisplay.dateTime = now.toISOString().slice(0, 10);
    const timeZone = new Intl.DateTimeFormat(undefined, { timeZoneName: 'short' })
      .formatToParts(now)
      .find((part) => part.type === 'timeZoneName')?.value;
    zoneDisplay.textContent = timeZone ? `Your local time · ${timeZone}` : 'Your local time';
  };

  updateClock();
  window.setTimeout(() => {
    updateClock();
    window.setInterval(updateClock, 1000);
  }, 1000 - (Date.now() % 1000));
});
