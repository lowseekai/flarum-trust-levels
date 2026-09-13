import Notification from 'flarum/forum/components/Notification';
import username from 'flarum/common/helpers/username';
import app from 'flarum/forum/app';
import TrustLevel from '../../common/models/TrustLevel';
import User from 'flarum/common/models/User';

export default class levelChangeNotification extends Notification {
    excerpt() {
        const currentLevel = (this.attrs.notification.subject() as TrustLevel);
        const from = this.attrs.notification.attribute<string>("data.from");
        const fromLevel = this.attrs.notification.attribute<number | undefined>("data.from_level");
        if (fromLevel === undefined) {
            return app.translator.trans('xypp-trust-levels.forum.notification.level-set-excerpt', {
                name: currentLevel.name(),
                level: currentLevel.level()
            });
        }
        return app.translator.trans('xypp-trust-levels.forum.notification.level-change-excerpt', {
            name: currentLevel.name(),
            level: currentLevel.level(),
            from, fromLevel
        });
    }
    icon() {
        const fromLevel = this.attrs.notification.attribute<number | undefined>("data.from_level");
        if (fromLevel === undefined) {
            return "fas fa-layer-group";
        }

        const currentLevel = (this.attrs.notification.subject() as TrustLevel).level();
        if (fromLevel > currentLevel) {
            return 'fas fa-level-down-alt';
        }
        return 'fas fa-level-up-alt';
    }

    href() {
        return app.route("user.trust-level",{
            username: (this.attrs.notification.fromUser() as User).slug(),
        });
    }

    content() {

        const currentLevel = (this.attrs.notification.subject() as TrustLevel);
        const from = this.attrs.notification.attribute<string>("data.from");
        const fromLevel = this.attrs.notification.attribute<number | undefined>("data.from_level");
        if (fromLevel === undefined) {
            return app.translator.trans('xypp-trust-levels.forum.notification.level', {
                name: currentLevel.name(),
                level: currentLevel.level()
            });
        }

        if (fromLevel < currentLevel.level()) {
            return app.translator.trans('xypp-trust-levels.forum.notification.level-up', {
                name: currentLevel.name(),
                level: currentLevel.level(),
                from, fromLevel
            });
        } else {
            return app.translator.trans('xypp-trust-levels.forum.notification.level-down', {
                name: currentLevel.name(),
                level: currentLevel.level(),
                from, fromLevel
            });
        }
    }
}
