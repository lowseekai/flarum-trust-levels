import Mithril from 'mithril';
import UserPage from 'flarum/forum/components/UserPage';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import app from 'flarum/forum/app';
import User from 'flarum/common/models/User';
import Placeholder from 'flarum/common/components/Placeholder';
import { Condition, getConditionMap, getConditions, HumanizeUtils, OPERATOR, userValueUtil, CALCULATE } from '../../collector/forum';
import { type ConditionData } from '../../collector/common/types/data';
import TrustLevel from '../../common/models/TrustLevel';
import { showIf } from '../../common/utils/NodeUtil';

type levelTable = {
  key: string;
  title: string;
  names: string[];
  level?: TrustLevel;
  target: number[];
  achieved: boolean[];
};

type ConditionProgress = {
  value: number;
  target: number;
  achieved: boolean;
  percent: number;
};

export class levelPage extends UserPage {
  loading: boolean = false;
  valueUtils?: userValueUtil;
  fields: ConditionData[] = [];
  fieldText: string[] = [];
  fieldValue: number[] = [];
  current: levelTable = {
    key: 'current',
    title: '',
    names: [],
    target: [],
    achieved: [],
  };
  next: levelTable = {
    key: 'next',
    title: '',
    names: [],
    target: [],
    achieved: [],
  };
  oninit(vnode: any) {
    super.oninit(vnode);
    this.user = null;
    this.loading = true;
    this.loadUser(m.route.param('username'));
  }

  show(user: User) {
    super.show(user);
    this.user = user;
    this.loadData();
  }
  async loadData() {
    this.fields = [];
    this.fieldText = [];
    this.fieldValue = [];
    this.current = {
      key: 'current',
      title: '',
      names: [],
      target: [],
      achieved: [],
    };
    this.next = {
      key: 'next',
      title: '',
      names: [],
      target: [],
      achieved: [],
    };

    const newUser = await app.store.find<User>('users', this.user!.id() + '', {
      include: 'trustLevel,trustLevel.next',
    });
    const humanize = HumanizeUtils.getInstance(app);
    await humanize.loadDefinition();

    let conditionMap: Record<string, Condition>;
    try {
      conditionMap = await getConditionMap(false, this.user);
    } catch (ignore) {
      conditionMap = {};
    }
    this.valueUtils = new userValueUtil(humanize, conditionMap);
    const currentLevel = newUser.trustLevel() || undefined;
    const nextLevel = currentLevel?.next() || undefined;

    [currentLevel, nextLevel].forEach((level) => {
      if (level) {
        (level.condition() || []).forEach((condition) => {
          if (
            this.fields.findIndex(
              (c) =>
                c.name === condition.name &&
                c.alter_name === condition.alter_name &&
                (c.calculate || CALCULATE.SUM) === (condition.calculate || CALCULATE.SUM) &&
                c.span === condition.span
            ) != -1
          ) {
            return;
          }
          const c = Object.assign({}, condition, { value: '' });
          const value = this.valueUtils!.getValue(condition);

          this.fields.push(c);
          this.fieldText.push(humanize.humanizeCondition(c));
          this.fieldValue.push(value);
        });
      }
    });

    [
      [currentLevel, this.current, 'xypp-trust-levels.forum.page.current-level'],
      [nextLevel, this.next, 'xypp-trust-levels.forum.page.next-level'],
    ].forEach((c: any) => {
      const level: TrustLevel | undefined = c[0];
      const data: levelTable = c[1];
      data.level = level;

      if (level) {
        (level.condition() || []).forEach((condition) => {
          const id = this.fields.findIndex(
            (c) =>
              c.name === condition.name &&
              c.alter_name === condition.alter_name &&
              (c.calculate || CALCULATE.SUM) === (condition.calculate || CALCULATE.SUM) &&
              c.span === condition.span
          );
          const value = this.fieldValue[id];
          data.target[id] = condition.value;
          data.achieved[id] = this.conditionOp(value, condition.operator, condition.value);
        });
        const translated = app.translator.trans(c[2], {
          name: level.name(),
        });
        data.title = Array.isArray(translated) ? translated.join('') : String(translated);
      } else {
        const translated = app.translator.trans(c[2], {
          name: app.translator.trans('xypp-trust-levels.forum.page.none'),
        });
        data.title = Array.isArray(translated) ? translated.join('') : String(translated);
      }
    });

    this.loading = false;
    m.redraw();
  }
  content() {
    if (!this.user || this.loading) {
      return <LoadingIndicator size={46} />;
    }
    return (
      <div className="TrustLevelPage container">
        <div className="TrustLevelPage-header">
          <div>
            <h2>{app.translator.trans('xypp-trust-levels.forum.page.title')}</h2>
            <p className="TrustLevelPage-subtitle">{this.current.title}</p>
          </div>
          <div className="TrustLevelPage-current-badge" aria-label={this.current.title}>
            <i className={this.levelIcon(this.current.level)} aria-hidden="true"></i>
            <span>{this.current.level ? `#${this.current.level.level()}` : '#'}</span>
          </div>
        </div>
        {this.makeOverview()}
        {this.makeProgress()}
      </div>
    );
  }

  makeOverview() {
    const currentLevel = this.current.level;
    const nextLevel = this.next.level;

    return (
      <div className="TrustLevelPage-overview">
        <div className="TrustLevelPage-overview-item TrustLevelPage-overview-item--current">
          <span className="TrustLevelPage-overview-label">
            {app.translator.trans('xypp-trust-levels.forum.page.current-level', {
              name: currentLevel?.name() || app.translator.trans('xypp-trust-levels.forum.page.none'),
            })}
          </span>
          <strong>{currentLevel ? `#${currentLevel.level()}` : '-'}</strong>
        </div>
        <div className="TrustLevelPage-overview-item TrustLevelPage-overview-item--next">
          <span className="TrustLevelPage-overview-label">
            {app.translator.trans('xypp-trust-levels.forum.page.next-level', {
              name: nextLevel?.name() || app.translator.trans('xypp-trust-levels.forum.page.none'),
            })}
          </span>
          <strong>{nextLevel ? `#${nextLevel.level()}` : '-'}</strong>
        </div>
        <div className="TrustLevelPage-overview-item TrustLevelPage-overview-item--progress">
          <span className="TrustLevelPage-overview-label">{app.translator.trans('xypp-trust-levels.forum.page.progress')}</span>
          <strong>{this.progressSummary()}</strong>
        </div>
      </div>
    );
  }

  makeProgress() {
    const nextConditions = this.next.level?.condition() || [];
    const hasNextLevel = !!this.next.level;

    return (
      <section className="TrustLevelPage-progress" aria-labelledby="trust-level-progress-title">
        <div className="TrustLevelPage-section-heading">
          <div>
            <h3 id="trust-level-progress-title">
              {hasNextLevel ? this.next.title : app.translator.trans('xypp-trust-levels.forum.page.current-progress')}
            </h3>
            <p>
              {hasNextLevel
                ? app.translator.trans('xypp-trust-levels.forum.page.progress-help')
                : app.translator.trans('xypp-trust-levels.forum.page.no-next-level-help')}
            </p>
          </div>
          <div className="TrustLevelPage-progress-count">{this.progressSummary()}</div>
        </div>
        {showIf(
          !nextConditions.length,
          <div className="TrustLevelPage-empty">
            <i className="fas fa-flag-checkered" aria-hidden="true"></i>
            <Placeholder
              text={
                hasNextLevel
                  ? app.translator.trans('xypp-trust-levels.forum.page.no-condition')
                  : app.translator.trans('xypp-trust-levels.forum.page.no-next-level')
              }
            />
          </div>,
          <div className="TrustLevelPage-condition-list">{nextConditions.map((condition, index) => this.makeProgressRow(condition, index))}</div>
        )}
      </section>
    );
  }

  makeProgressRow(condition: ConditionData, index: number) {
    const fieldIndex = this.findFieldIndex(condition);
    const progress = this.conditionProgress(condition, fieldIndex);
    const label = this.fieldText[fieldIndex] || HumanizeUtils.getInstance(app).humanizeCondition(condition);

    return (
      <div className={'TrustLevelPage-condition ' + (progress.achieved ? 'is-achieved' : '')} key={`${condition.name}-${index}`}>
        <div className="TrustLevelPage-condition-icon">
          <i className={progress.achieved ? 'fas fa-check' : 'fas fa-arrow-up'} aria-hidden="true"></i>
        </div>
        <div className="TrustLevelPage-condition-body">
          <div className="TrustLevelPage-condition-heading">
            <span className="TrustLevelPage-condition-name">{label}</span>
            <span className="TrustLevelPage-condition-value">
              {progress.value} / {progress.target}
            </span>
          </div>
          <div className="TrustLevelPage-condition-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow={progress.percent}>
            <span style={{ width: `${progress.percent}%` }}></span>
          </div>
        </div>
        <span className="TrustLevelPage-condition-status">
          {progress.achieved
            ? app.translator.trans('xypp-trust-levels.forum.page.achieved')
            : app.translator.trans('xypp-trust-levels.forum.page.in-progress')}
        </span>
      </div>
    );
  }

  findFieldIndex(condition: ConditionData) {
    return this.fields.findIndex(
      (field) =>
        field.name === condition.name &&
        field.alter_name === condition.alter_name &&
        (field.calculate || CALCULATE.SUM) === (condition.calculate || CALCULATE.SUM) &&
        field.span === condition.span
    );
  }

  conditionProgress(condition: ConditionData, index: number): ConditionProgress {
    const value = index === -1 ? 0 : this.fieldValue[index] || 0;
    const target = Number(condition.value || 0);
    const achieved = this.conditionOp(value, condition.operator, target);
    let percent = 0;

    if (achieved) {
      percent = 100;
    } else if (condition.operator === OPERATOR.NOT_EQUAL) {
      percent = 0;
    } else if (condition.operator === OPERATOR.LESS_THAN || condition.operator === OPERATOR.LESS_THAN_OR_EQUAL) {
      percent = target <= 0 ? 0 : Math.max(0, Math.min(99, Math.round(((target - value) / target) * 100)));
    } else if (target <= 0) {
      percent = 0;
    } else {
      percent = Math.max(0, Math.min(99, Math.round((value / target) * 100)));
    }

    return { value, target, achieved, percent };
  }

  progressSummary() {
    const conditions = this.next.level?.condition() || [];
    if (!conditions.length) {
      return this.next.level ? '0 / 0' : '-';
    }
    const completed = conditions.filter((condition) => {
      const index = this.findFieldIndex(condition);
      return this.conditionProgress(condition, index).achieved;
    }).length;
    return `${completed} / ${conditions.length}`;
  }

  levelIcon(level?: TrustLevel) {
    return level?.icon()?.trim() || 'fas fa-layer-group';
  }

  conditionOp(value1: number, op: OPERATOR, value2: number) {
    switch (op) {
      case OPERATOR.EQUAL:
        return value1 == value2;
      case OPERATOR.GREATER_THAN:
        return value1 > value2;
      case OPERATOR.GREATER_THAN_OR_EQUAL:
        return value1 >= value2;
      case OPERATOR.LESS_THAN:
        return value1 < value2;
      case OPERATOR.LESS_THAN_OR_EQUAL:
        return value1 <= value2;
      case OPERATOR.NOT_EQUAL:
        return value1 != value2;
    }
  }
}
