import Model from 'flarum/common/Model';
import { CALCULATE, ConditionAccumulation, ConditionData, RewardData } from '../types/data';
import dayjs from 'dayjs';
// For more details about frontend models
// checkout https://docs.flarum.org/extend/models.html#frontend-models
function optionalJsonParser<T>(data: any): T | null {
  if (!data) {
    return null
  };
  try {
    return JSON.parse(data);
  } catch (ignore) {
    return null;
  }
}

const META_FIELDS = ["all", "rest", "flg", "max", "days"];

export default class Condition extends Model {
  name = Model.attribute<string>('name');
  value = Model.attribute<number>('value');
  user_id = Model.attribute<number>('user_id');
  global = Model.attribute<boolean>('global');
  accumulation = Model.attribute<ConditionAccumulation | null>('accumulation', optionalJsonParser<ConditionAccumulation>);
  getSpan(span: number, calculate: CALCULATE = CALCULATE.SUM): number {
    if (!calculate) calculate = CALCULATE.SUM;
    const accumulation = this.accumulation();
    if (!accumulation || span < 1) return 0;
    let cut = dayjs(dayjs().format("YYYYMMDD"), "YYYYMMDD");
    if (span != 1) {
      cut = cut.subtract(span - 1, 'day');
    }
    let ret = 0;
    Object.keys(accumulation).forEach((key: string) => {
      if (META_FIELDS.includes(key)) return;
      const d = dayjs(key, "YYYYMMDD");
      if (d.isAfter(cut) || d.isSame(cut)) {
        if (calculate == CALCULATE.MAX)
          ret = Math.max(ret, accumulation[key]);
        else if (calculate == CALCULATE.SUM)
          ret += accumulation[key];
        else if (calculate == CALCULATE.DAY_COUNT && accumulation[key] > 0)
          ret += 1;
      }
    });
    return ret;
  }

  getTotal(calculate: CALCULATE = CALCULATE.SUM) {
    if (!calculate) calculate = CALCULATE.SUM;
    if (calculate == CALCULATE.MAX) return this.accumulation()?.max || 0;
    else if (calculate == CALCULATE.DAY_COUNT) return this.accumulation()?.days || 0;
    else return this.accumulation()?.all || 0;
  }
}