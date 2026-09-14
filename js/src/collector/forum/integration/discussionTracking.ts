import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import { extend } from 'flarum/common/extend';
import { triggerCondition } from '../utils/frontendTrigger';

type TrackingState = {
  discussionId: string;
  interval: number;
  lastTimestamp: number;
  pendingMinutes: number;
  onVisibilityChange: () => void;
};

const trackingStates = new WeakMap<object, TrackingState>();

function recordDiscussionView(discussionId: string): void {
  app
    .request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/trust-level-discussion-view',
      body: { discussionId },
    })
    .catch(() => undefined);
}

function flushReadingTime(state: TrackingState): void {
  const now = Date.now();

  if (document.visibilityState === 'visible') {
    state.pendingMinutes += (now - state.lastTimestamp) / 60000;
  }

  state.lastTimestamp = now;

  const minutes = Math.floor(state.pendingMinutes);

  if (minutes < 1) {
    return;
  }

  state.pendingMinutes -= minutes;
  triggerCondition('reading_time', minutes).catch(() => undefined);
}

export function registerDiscussionTracking(): void {
  extend(DiscussionPage.prototype, 'show', function (_value: any, discussion: any) {
    if (!app.session?.user || !discussion?.id()) {
      return;
    }

    const previous = trackingStates.get(this);

    if (previous) {
      window.clearInterval(previous.interval);
      document.removeEventListener('visibilitychange', previous.onVisibilityChange);
    }

    const state: TrackingState = {
      discussionId: String(discussion.id()),
      interval: 0,
      lastTimestamp: Date.now(),
      pendingMinutes: 0,
      onVisibilityChange: () => {
        flushReadingTime(state);
      },
    };

    recordDiscussionView(state.discussionId);
    state.interval = window.setInterval(() => flushReadingTime(state), 15000);
    document.addEventListener('visibilitychange', state.onVisibilityChange);
    trackingStates.set(this, state);
  });

  extend(DiscussionPage.prototype, 'onremove', function (_value: any) {
    const state = trackingStates.get(this);

    if (!state) {
      return;
    }

    flushReadingTime(state);
    window.clearInterval(state.interval);
    document.removeEventListener('visibilitychange', state.onVisibilityChange);
    trackingStates.delete(this);
  });
}
