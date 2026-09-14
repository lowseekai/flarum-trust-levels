import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import TrustLevel from '../common/models/TrustLevel';
import UserPage from 'flarum/forum/components/UserPage';
import { extend } from 'flarum/common/extend';
import LinkButton from 'flarum/common/components/LinkButton';
import { levelPage } from './components/levelPage';
import levelChangeNotification from './notification/levelChangeNotification';
import Group from 'flarum/common/models/Group';
import { init } from '../collector/common/integration';
import { registerCount } from '../collector/forum/integration/pageCount';
import { registerDiscussionTracking } from '../collector/forum/integration/discussionTracking';

const groupLoadAttempts = new Set<string>();
const groupLoadFailures = new Set<string>();

app.initializers.add('xypp/flarum-trust-levels', () => {
  init(app, 'forum');
  registerCount();
  registerDiscussionTracking();

  User.prototype.trustLevel = Model.hasOne<TrustLevel>('trustLevel') as any;
  app.routes['user.trust-level'] = {
    path: '/u/:username/trust-level',
    component: levelPage,
  };

  extend(UserPage.prototype, 'navItems', function (items) {
    if (app.session.user) {
      items.add(
        'trust-levels',
        LinkButton.component(
          {
            href: app.route('user.trust-level', { username: this.user?.username() }),
            icon: 'fas fa-layer-group',
          },
          [
            app.translator.trans('xypp-trust-levels.forum.nav-title')
          ]
        ),
        10
      );
    }
  });

  // A user response can contain group linkage before the group resources have
  // reached the client store. Core's User#badges then creates an empty badge
  // for each unresolved group. Remove that placeholder and request the group
  // resources so the real badge can render on the next redraw.
  extend(User.prototype, 'badges', function (items) {
    const groups = this.groups();

    if (!Array.isArray(groups)) {
      return;
    }

    if (!groups.some((group) => !group)) {
      return;
    }

    // Core creates a GroupBadge even when a relationship points to a group
    // that has not been added to the store yet. Never render that empty badge.
    items.remove('groupundefined');

    const relationshipData = this.data.relationships?.groups?.data;
    const identifiers = Array.isArray(relationshipData) ? relationshipData : [];
    const missingIds = Array.from(new Set(identifiers
      .filter((identifier) => identifier.type === 'groups')
      .filter((identifier) => !app.store.getById<Group>('groups', identifier.id))
      .map((identifier) => identifier.id)));
    const loadableIds = missingIds.filter((id) => !groupLoadFailures.has(id));

    if (!loadableIds.length) {
      return;
    }

    const key = loadableIds.slice().sort().join(',');

    if (!groupLoadAttempts.has(key)) {
      groupLoadAttempts.add(key);
      Promise.all(loadableIds.map((id) => app.store.find<Group>('groups', id)))
        .then(() => {
          groupLoadAttempts.delete(key);
          loadableIds.forEach((id) => groupLoadFailures.delete(id));
          m.redraw();
        })
        .catch(() => {
          // Hidden/deleted groups can legitimately return 404. Mark them as
          // failed so every redraw does not start another request loop.
          groupLoadAttempts.delete(key);
          loadableIds.forEach((id) => groupLoadFailures.add(id));
        });
    }
  });

  // Flarum 2 no longer exports NotificationGrid as a frontend module.
  // String targets keep this extender compatible with both Flarum 1 and 2.
  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('trust_level_change', {
      name: 'trust_level_change',
      icon: 'fas fa-layer-group',
      label: app.translator.trans('xypp-trust-levels.forum.notification.name')
    });
  });

  app.notificationComponents.trust_level_change = levelChangeNotification;
});
