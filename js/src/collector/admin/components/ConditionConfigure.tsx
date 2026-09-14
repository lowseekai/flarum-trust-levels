import Component from 'flarum/common/Component';
import { CALCULATE, ConditionData, OPERATOR } from '../../common/types/data';
import Stream from 'mithril/stream';
import app from 'flarum/admin/app';
import HumanizeUtils from '../../common/utils/HumanizeUtils';
import { showIf } from '../../common/utils/NodeUtil';
import Button from 'flarum/common/components/Button';
import Select from 'flarum/common/components/Select';

function noNewItem(c: any): boolean {
  return c.name !== '*';
}

export default class ConditionConfigure extends Component<{ conditions: Stream<ConditionData[]> }> {
  conditions: ConditionData[] = [];
  REG_OPERATOR: Record<string, string> = {
    '=': '=',
    '>': '>',
    '>=': '>=',
    '<': '<',
    '<=': '<=',
    '!=': '!=',
  };
  REG_CALCULATE: Record<string, string> = {};
  REG_CONDITIONS: Record<string, string> = {};

  oninit(vnode: any): void {
    super.oninit(vnode);
    const humanize = HumanizeUtils.getInstance(app);
    const conditions = humanize.getAllConditions().toObject();
    Object.keys(conditions).forEach((item) => {
      this.REG_CONDITIONS[item] = conditions[item].content;
    });

    this.REG_CONDITIONS['*'] = app.translator.trans('xypp-collector.admin.list.new_item') + '';
    this.REG_CALCULATE[CALCULATE.SUM] = humanize.getCalculate(CALCULATE.SUM) + '';
    this.REG_CALCULATE[CALCULATE.MAX] = humanize.getCalculate(CALCULATE.MAX) + '';
    this.REG_CALCULATE[CALCULATE.DAY_COUNT] = humanize.getCalculate(CALCULATE.DAY_COUNT) + '';
    this.conditions = JSON.parse(JSON.stringify(this.attrs.conditions()));
    this.conditions.push({
      name: '*',
      operator: OPERATOR.EQUAL,
      value: 0,
    });
  }
  onbeforeupdate(vnode: any): void {
    this.conditions = JSON.parse(JSON.stringify(this.attrs.conditions()));
    this.conditions.push({
      name: '*',
      operator: OPERATOR.EQUAL,
      value: 0,
    });
    super.onbeforeupdate(vnode);
  }
  view(vnode: any) {
    return (
      <div className="condition-table-wrapper">
        <table className="Table condition-table">
          <colgroup>
            <col className="condition-table-col-name" />
            <col className="condition-table-col-operator" />
            <col className="condition-table-col-value" />
            <col className="condition-table-col-span" />
            <col className="condition-table-col-calculate" />
            <col className="condition-table-col-alter-name" />
            <col className="condition-table-col-actions" />
          </colgroup>
          <thead>
            <tr>
              <th>{app.translator.trans('xypp-collector.admin.list.condition-name')}</th>
              <th>{app.translator.trans('xypp-collector.admin.list.condition-operator')}</th>
              <th>{app.translator.trans('xypp-collector.admin.list.condition-value')}</th>
              <th>{app.translator.trans('xypp-collector.admin.list.condition-span')}</th>
              <th>{app.translator.trans('xypp-collector.admin.list.condition-calculate')}</th>
              <th>{app.translator.trans('xypp-collector.admin.list.condition-alter_name')}</th>
              <th>{app.translator.trans('xypp-collector.admin.list.condition-actions')}</th>
            </tr>
          </thead>
          <tbody>
            {this.conditions.map((item, index) => {
              return (
                <tr key={`${item.name}-${index}`}>
                  <td>
                    <Select
                      className="FormControl"
                      value={item.name}
                      options={this.REG_CONDITIONS}
                      onchange={((name: string) => {
                        if (this.conditions.length == index + 1) {
                          this.conditions.push({
                            name: '*',
                            operator: OPERATOR.EQUAL,
                            value: 0,
                          });
                        }
                        this.conditions[index].name = name;
                        this.attrs.conditions(this.conditions.filter(noNewItem));
                      }).bind(this)}
                    ></Select>
                  </td>
                  <td>
                    <Select
                      className="FormControl"
                      value={item.operator}
                      options={this.REG_OPERATOR}
                      onchange={((name: string) => {
                        this.conditions[index].operator = name as OPERATOR;
                        this.attrs.conditions(this.conditions.filter(noNewItem));
                      }).bind(this)}
                    ></Select>
                  </td>
                  <td>
                    <input
                      className="FormControl"
                      type="number"
                      value={item.value}
                      onchange={((e: InputEvent) => {
                        const value = parseInt((e.target as HTMLInputElement).value, 10);
                        this.conditions[index].value = Number.isNaN(value) ? 0 : value;
                        this.attrs.conditions(this.conditions.filter(noNewItem));
                      }).bind(this)}
                    />
                  </td>
                  <td>
                    <input
                      className="FormControl"
                      type="number"
                      min="1"
                      value={item.span || ''}
                      onchange={((e: InputEvent) => {
                        const value = parseInt((e.target as HTMLInputElement).value, 10);
                        this.conditions[index].span = Number.isNaN(value) ? undefined : value;
                        this.attrs.conditions(this.conditions.filter(noNewItem));
                      }).bind(this)}
                    />
                  </td>
                  <td>
                    <Select
                      className="FormControl"
                      value={item.calculate || CALCULATE.SUM}
                      options={this.REG_CALCULATE}
                      onchange={((name: string) => {
                        this.conditions[index].calculate = parseInt(name, 10) as CALCULATE;
                        this.attrs.conditions(this.conditions.filter(noNewItem));
                      }).bind(this)}
                    ></Select>
                  </td>
                  <td>
                    <input
                      className="FormControl"
                      type="text"
                      value={item.alter_name || ''}
                      onchange={((e: InputEvent) => {
                        this.conditions[index].alter_name = (e.target as HTMLInputElement).value || undefined;
                        this.attrs.conditions(this.conditions.filter(noNewItem));
                      }).bind(this)}
                    />
                  </td>
                  <td>
                    <div className="condition-table-actions">
                      {showIf(
                        item.name != '*',
                        <Button
                          className="Button Button--danger Button--icon"
                          icon="fas fa-trash"
                          aria-label={app.translator.trans('xypp-collector.admin.list.condition-delete')}
                          onclick={((e: any) => {
                            this.conditions.splice(index, 1);
                            m.redraw();
                            this.attrs.conditions(this.conditions.filter(noNewItem));
                          }).bind(this)}
                          data-id={index}
                        />
                      )}
                      {showIf(
                        this.conditions[index - 1] && item.name != '*',
                        <Button
                          className="Button Button--secondary Button--icon"
                          icon="fas fa-sort-up"
                          aria-label={app.translator.trans('xypp-collector.admin.list.condition-move-up')}
                          onclick={this.swap(index, -1)}
                        />
                      )}
                      {showIf(
                        this.conditions[index + 2] && item.name != '*',
                        <Button
                          className="Button Button--secondary Button--icon"
                          icon="fas fa-sort-down"
                          aria-label={app.translator.trans('xypp-collector.admin.list.condition-move-down')}
                          onclick={this.swap(index, 1)}
                        />
                      )}
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    );
  }

  swap(id: number, dir: number) {
    return (() => {
      const swap1 = Math.max(id + dir, id);
      const swap2 = Math.min(id + dir, id);
      const tmp = this.conditions[swap1];
      this.conditions[swap1] = this.conditions[swap2];
      this.conditions[swap2] = tmp;
      this.attrs.conditions(this.conditions.filter(noNewItem));
      m.redraw();
    }).bind(this);
  }
}
