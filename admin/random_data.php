<?php
require("admin-header.php");
?>
<title>랜덤 데이터 생성기</title>
<style>
  .random-data-title { margin: 0 0 16px; text-align: center; }
  .random-data-row { display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; }
  .random-data-row textarea { flex: 1 1 360px; min-height: 260px; min-width: 320px; box-sizing: border-box; font-family: monospace; }
  .random-data-actions { margin: 14px 0; text-align: center; }
  #output { min-height: 220px; width: 100%; box-sizing: border-box; font-family: monospace; }
  .random-data-help { text-align: left; white-space: pre-wrap; font-family: monospace; line-height: 1.6; }
</style>
<hr>
<h3 class="random-data-title">랜덤 데이터 생성기</h3>

<div class="container">
  <div class="random-data-row">
    <textarea id="input"></textarea>
  </div>
  <div class="random-data-actions">
    <button id="click" class="btn btn-primary">무작위로 생성</button>
  </div>
  <textarea id="output" readonly></textarea>
  <hr>
  <pre class="random-data-help">현재 두 가지 문장을 지원합니다
constraint  제약 - 변수가 가질 수 있는 값의 범위를 제한합니다
repeat      반복 - 내용을 반복해서 출력합니다

중요: 모든 변수는 반드시 constraint(제약)가 있어야 합니다

constraint 문법

int 타입
constraint 변수명 int 최소값 최대값
예시
constraint n int 1 233
변수명은 영문 알파벳을 권장합니다 (한글도 가능합니다)
최소값과 최대값은 다른 변수명으로 지정할 수도 있습니다
구간은 [최소값, 최대값) 입니다 (최소값 포함, 최대값 미포함)

set 타입
constraint 변수명 set 값1 값2 값3
예시
constraint 숫자 set 하나 둘 셋 넷
값들은 공백으로 구분합니다

repeat 문법

line 타입
repeat 반복횟수 반복할내용
반복횟수는 숫자이거나 변수일 수 있습니다
반복횟수 뒤의 공백부터 그 줄의 끝까지가 템플릿으로 처리되며, ${변수명} 형태로 변수를 참조합니다
예시
repeat 1 ${n}
또는
repeat 1 prefix string ${n}
모두 가능합니다

group 타입
repeat group 반복횟수
repeat line
repeat line
repeat line
end group
반복횟수는 숫자이거나 변수일 수 있습니다

예를 들어 데이터가 다음과 같다면
첫 줄에 n이 입력되고, 이어서 n개의 줄에 각각 세 개의 수 a b c가 있는 경우
아래처럼 작성합니다
repeat 1 ${n}
repeat n ${a} ${b} ${c}
그 다음 constraint(제약)를 추가하면 됩니다

또 다른 예시
데이터가 다음과 같은 모양이라면
첫 줄이 n이고, 이어서 n개의 그룹이 있는 경우
각 그룹의 첫 줄에는 row min max 세 개의 수가 있고, 이어서 row개의 숫자가 있으며 각 숫자의 값은 [min, max) 범위인 경우
다음과 같이 작성할 수 있습니다

constraint n int 5 10
constraint row int 10 20
constraint min int 150 200
constraint max int 500 1000
constraint num int min max
repeat 1 ${n}
repeat group n
repeat 1 ${row} ${min} ${max}
repeat row ${num}
end group

set 예시도 하나 들어보면

constraint 성 set 김 이 박 최
constraint 이름 set 민준 서연 지훈 하은
repeat 10 ${성}${이름}

프로그램이 입력값이 올바른지 검사하지는 않으므로 직접 확인해 주세요 :)</pre>
</div>

<script>
(function(){

// ---- lib ----
function shuffleArray(array) {
  for (let i = array.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    const temp = array[i];
    array[i] = array[j];
    array[j] = temp;
  }
  return array;
}
function isString(maybe) {
  return (typeof maybe) === 'string';
}
function isUndefined(value) {
  return value === undefined;
}
function getRandomInt(min, max) {
  let fix = 0;
  if (min < 0) {
    fix = min;
    min = 0;
    max -= fix;
  }
  return Math.floor(Math.random() * (max - min)) + min + fix;
}
function range(start, end) {
  const ret = [];
  while (start < end) {
    ret.push(start);
    start += 1;
  }
  return ret;
}
function splitArray(list, num) {
  const indexList = range(1, list.length);
  shuffleArray(indexList);
  const usedIndex = indexList.slice(0, num - 1).sort();
  const ret = [];
  let start = 0;
  usedIndex.forEach((value) => {
    ret.push(list.slice(start, value));
    start = value;
  });
  ret.push(list.slice(start));
  return ret;
}
function valueOf(expStr, getValue) {
  const func = new Function('env', `with(env){return ${expStr}}`);
  const env = new Proxy({}, {
    get: function(_, key){
      const value = getValue(key);
      return (isUndefined(value) ? window[key] : value);
    },
    has: function (_, key) {
      return true;
    },
  });
  return func(env);
}
function removeItem(arr, item) {
  const index = arr.indexOf(item);
  if (index >= 0) {
    arr.splice(index, 1);
  }
}
const Flag = {
  shuffle: 'shuffle',
  link: 'link',
  unlink: 'unlink',
};
const Type = {
  int: 'int',
  set: 'set',
  graph: 'graph',
  alias: 'alias',
};

// ---- parse ----
function isFunctionFlag (str) {
  return str.includes('(')
}
function parseFlag(flagPart) {
  const ret = {};
  if (isString(flagPart)) {
    flagPart.split(' ').forEach((flagItem) => {
      const key = flagItem.trim();
      if (key) {
        if (isFunctionFlag(key)) {
          const preStr = key.replace(/[(),]/g, ' ')
          const [flagName, ...param] = preStr.split(' ')
          if (!Array.isArray(ret[flagName])) {
            ret[flagName] = []
          }
          ret[flagName].push(param)
        } else {
          ret[key] = true;
        }
      }
    });
  }
  return ret;
}
function isConstraint(line) {
  return line.startsWith('constraint');
}
function isRepeat(line) {
  return line.startsWith('repeat');
}
function isRepeatGroup(line) {
  return line.startsWith('repeat group');
}
function isGroupEnd(line) {
  return line.startsWith('end group');
}
const repeatGroupStartLength = 'repeat group '.length;
function parse(input) {
  const ret = [];
  const constraint = {};
  function addIntConstraint(name, [lower, higher], flag) {
    constraint[name] = {
      lower: lower,
      higher: higher,
      type: 'int',
      flag
    };
  }
  function addSetConstraint(name, list, flag) {
    constraint[name] = {
      list: list,
      type: 'set',
      flag
    };
  }
  function addGraphConstraint(name, [graphNum, nodeNum, edgeNum], flag) {
    constraint[name] = {
      graphNum,
      nodeNum,
      edgeNum,
      type: 'graph',
      flag
    };
  }
  function addAliasConstraint(name, aliasName) {
    constraint[name] = {
      aliasName,
      type: 'alias',
    };
  }
  const list = input.split('\n');
  let index = 0;
  const end = list.length;
  while (index !== end) {
    const line = list[index];
    while (true) {
      if (isConstraint(line)) {
        const [definePart, flagsPart] = line.split('|');
        const [_, name, type, ...other] = definePart.split(' ');
        const flag = parseFlag(flagsPart);
        if (type === 'int') {
          addIntConstraint(name, other, flag);
          break;
        }
        if (type === 'set') {
          addSetConstraint(name, other, flag);
          break;
        }
        if (type === 'graph') {
          addGraphConstraint(name, other, flag);
          break;
        }
        if (type === 'alias') {
          addAliasConstraint(name, other[0]);
          break;
        }
        break;
      }
      if (isRepeatGroup(line)) {
        const repeat = line.substr(repeatGroupStartLength);
        const children = [];
        index += 1;
        while (!isGroupEnd(list[index])) {
          const repeatLine = list[index];
          const repeatStart = 'repeat '.length;
          const repeatEnd = repeatLine.indexOf(' ', repeatStart);
          const repeat = repeatLine.substr(repeatStart, repeatEnd - repeatStart);
          children.push({
            template: repeatLine.substr(repeatEnd + 1),
            repeat: repeat,
            type: 'line'
          });
          index += 1;
        }
        ret.push(
          {
            type: 'group',
            repeat: repeat,
            children: children
          }
        );
        break;
      }
      if (isRepeat(line)) {
        const repeatStart = 'repeat '.length;
        const repeatEnd = line.indexOf(' ', repeatStart);
        const repeat = line.substr(repeatStart, repeatEnd - repeatStart);
        ret.push({
          template: line.substr(repeatEnd + 1),
          repeat: repeat,
          type: 'line',
        });
        break;
      }
      break;
    }
    index += 1;
  }
  return [ret, constraint];
}

// ---- generate ----
function hasEdge(edgeList, edge) {
  const v1 = edge[0];
  const v2 = edge[1];
  return edgeList.some((item) => {
    return item[0] === v1 && item[1] === v2;
  })
}
function maxEdge(nodeNum) {
  return (nodeNum * (nodeNum - 1)) / 2;
}
function checkUnlink(constraint, edge) {
  return !constraint.some(constraintItem => constraintItem[0] === edge[0] && constraintItem[1] === edge[1]);
}
function getRandomSubGraph(nodeList, edgeNumber, staticConstraint) {
  const ret = [];
  const left = [];
  const right = nodeList.slice();
  const nodeCount = nodeList.length;
  const maxEdgeCount = maxEdge(nodeCount);
  const neededEdgeCount = edgeNumber;
  let edgeCount = 0;
  const effectiveUnlink = staticConstraint[Flag.unlink].filter(([node1, node2]) => {
    return nodeList.includes(node1) && nodeList.includes(node2);
  });
  const effectiveLink = staticConstraint[Flag.link].filter(([node1, node2]) => {
    return nodeList.includes(node1) && nodeList.includes(node2);
  });
  if ((neededEdgeCount + effectiveUnlink.length) > maxEdgeCount) {
    throw new Error('간선의 수가 너무 많아 그래프를 생성할 수 없습니다');
  }
  if (effectiveUnlink.length > edgeNumber) {
    throw new Error('[flag][link] 제약이 너무 많아 edgeNumber 조건을 만족할 수 없습니다');
  }
  if (effectiveLink.length) {
    effectiveLink.forEach((edge) => {
      if (edge[0] > edge[1]) {
        edge = [edge[1], edge[0]];
      }
      ret.push(edge);
      left.push(edge[0], edge[1]);
      removeItem(right, edge[0]);
      removeItem(right, edge[1]);
      edgeCount += 1;
    });
  } else {
    const pickMe = getRandomInt(0, right.length);
    left.push(right[pickMe]);
    right.splice(pickMe, 1);
  }
  shuffleArray(right);
  let whileCount = 0;
  while (right.length && whileCount < 233) {
    whileCount += 1;
    const rightNode = right[0];
    const pickMe = getRandomInt(0, left.length);
    const leftNode = left[pickMe];
    const edge = [leftNode, rightNode].sort((a, b) => a - b);
    if (!checkUnlink(effectiveUnlink, edge)) {
      continue;
    }
    ret.push(edge);
    left.push(rightNode);
    right.shift();
  }
  if (whileCount === 233) {
    throw new Error('반복이 너무 많습니다. 버그로 보입니다');
  }
  while (neededEdgeCount > ret.length) {
    let i1;
    let i2;
    i1 = getRandomInt(0, nodeCount - 1);
    if (i1 === (nodeCount - 2)) {
      i2 = nodeCount - 1;
    } else {
      i2 = getRandomInt(i1 + 1, nodeCount)
    }
    const edge = [left[i1], left[i2]].sort((a, b) => a - b);
    if (hasEdge(ret, edge)) {
      continue;
    }
    if (!checkUnlink(effectiveUnlink, edge)) {
      continue;
    }
    ret.push(edge);
  }
  return ret;
}
function generate(list, constraint) {
  function getRandomValue(store, name) {
    if (!(name in constraint)) {
      return;
    }
    const constraintItem = constraint[name];
    let value = {};
    switch (constraintItem.type) {
      case 'int': {
        value.type = Type.int;
        const { lower, higher, flag } = constraintItem;
        const min = getValueFromString(store, lower);
        const max = getValueFromString(store, higher);
        if (flag[Flag.shuffle]) {
          const hasShuffleObj = (typeof constraintItem[Flag.shuffle]) === 'object';
          if (!hasShuffleObj) {
            constraintItem[Flag.shuffle] = {
              list: shuffleArray(range(min, max)),
              index: 0,
            };
          }
          const shuffleObj = constraintItem[Flag.shuffle];
          if (shuffleObj.index === shuffleObj.list.length) {
            shuffleObj.list = shuffleArray(range(min, max));
            shuffleObj.index = 0;
          }
          value.value = shuffleObj.list[shuffleObj.index];
          shuffleObj.index += 1;
        } else {
          value.value = getRandomInt(min, max);
        }
        break;
      }
      case 'set': {
        value.type = Type.set;
        const { list, flag } = constraintItem;
        if (flag[Flag.shuffle]) {
          const hasShuffleObj = (typeof constraintItem[Flag.shuffle]) === 'object';
          if (!hasShuffleObj) {
            constraintItem[Flag.shuffle] = {
              list: shuffleArray(list.slice()),
              index: 0,
            };
          }
          const shuffleObj = constraintItem[Flag.shuffle];
          if (shuffleObj.index === shuffleObj.list.length) {
            shuffleObj.list = shuffleArray(list.slice());
            shuffleObj.index = 0;
          }
          value.value = shuffleObj.list[shuffleObj.index];
          shuffleObj.index += 1;
        } else {
          value.value = list[getRandomInt(0, list.length)];
        }
        break;
      }
      case 'graph': {
        value.type = Type.graph;
        const rret = getRandomGraph(store, constraintItem);
        value.value = rret.value;
        value.nodeList = rret.nodeList;
        break;
      }
      case 'alias': {
        value.type = Type.alias;
        const rret = getRandomValue(store, constraintItem.aliasName);
        value.value = rret.value;
        break;
      }
    }
    store[name] = value.value;
    return value;
  }
  function getRandomGraph(store, config) {
    let ret = [];
    const { nodeNum, edgeNum, graphNum, flag } = config;
    const graphValue = getValueFromString(store, graphNum);
    const nodeValue = getValueFromString(store, nodeNum);
    const edgeValue = getValueFromString(store, edgeNum);
    const nodeList = range(1, nodeValue + 1);
    const staticConstraint = {
      [Flag.link]: [],
      [Flag.unlink]: [],
    };
    if (Array.isArray(flag[Flag.link])) {
      flag[Flag.link].forEach(([n1, n2]) => {
        const node1 = getValueFromString(store, n1);
        const node2 = getValueFromString(store, n2);
        staticConstraint[Flag.link].push([node1, node2].sort((a, b) => a - b));
      });
    }
    if (Array.isArray(flag[Flag.unlink])) {
      flag[Flag.unlink].forEach(([n1, n2]) => {
        const node1 = getValueFromString(store, n1);
        const node2 = getValueFromString(store, n2);
        staticConstraint[Flag.unlink].push([node1, node2].sort((a, b) => a - b));
      });
    }
    if (graphValue === 1) {
      Array.prototype.push.apply(ret, getRandomSubGraph(nodeList, edgeValue, staticConstraint));
    } else {
      const nodeListArr = splitArray(nodeList, graphValue);
      let allEdge = 0;
      nodeListArr.forEach((list) => {
        allEdge += maxEdge(list.length);
      });
      if (allEdge < edgeValue) {
        throw new Error('간선의 수가 너무 많아 제약을 만족하는 그래프를 생성할 수 없습니다');
      }
      const p = edgeValue / allEdge;
      let usedEdge = 0;
      nodeListArr.forEach((list, index) => {
        const isLast = index === (nodeListArr.length - 1);
        const subMaxEdge = maxEdge(list.length);
        if (subMaxEdge === 0 || edgeValue === usedEdge) {
          if (list.length === 1) {
            return;
          }
          throw new Error('각 부분 그래프는 서로 연결되어 있어야 합니다');
        }
        if (isLast) {
          Array.prototype.push.apply(ret, getRandomSubGraph(list, edgeValue - usedEdge, staticConstraint));
        } else {
          let subRealEdge = Math.min(subMaxEdge, Math.floor(p * subMaxEdge));
          subRealEdge = Math.max(subRealEdge, list.length - 1);
          if ((subRealEdge + usedEdge) > edgeValue) {
            Array.prototype.push.apply(ret, getRandomSubGraph(list, edgeValue - usedEdge, staticConstraint));
            usedEdge = edgeValue;
          } else {
            usedEdge += subRealEdge;
            Array.prototype.push.apply(ret, getRandomSubGraph(list, subRealEdge, staticConstraint));
          }
        }
      });
    }
    return { value: ret, nodeList };
  }
  function getValue(store, name) {
    if (!store.hasOwnProperty(name)) {
      return getRandomValue(store, name);
    }
    return { value: store[name] };
  }
  function getValueFromString(store, name) {
    const handler = (varName) => {
      const ret = getValue(store, varName);
      if (ret) {
        return ret.value;
      }
      return ret;
    };
    return valueOf(name, handler);
  }
  function valueOfTemplate(store, template) {
    const values = [];
    const matchArr = template.match(/\${(.+?)}/g);
    if (Array.isArray(matchArr)) {
      matchArr.forEach((matched) => {
        values.push(
          getRandomValue(store, matched.slice(2, -1))
        );
      });
    }
    return values;
  }
  const repeator = {
    line(store, repeat, template) {
      let i = 0;
      let ret = [];
      while (i !== repeat) {
        ret.push({
          template,
          values: valueOfTemplate(store, template)
        });
        i += 1;
      }
      return ret;
    },
    group(store, repeat, children) {
      let i = 0;
      let ret = [];
      while (i !== repeat) {
        children.forEach((item) => {
          const repeat = getValueFromString(store, item.repeat);
          Array.prototype.push.apply(ret, repeator.line(store, repeat, item.template));
        })
        i += 1;
      }
      return ret;
    }
  }
  const store = {};
  let ret = [];
  list.forEach((item) => {
    switch (item.type) {
      case 'line': {
        const repeat = getValueFromString(store, item.repeat);
        Array.prototype.push.apply(ret, repeator.line(store, repeat, item.template));
        break;
      }
      case 'group': {
        const repeat = getValueFromString(store, item.repeat);
        Array.prototype.push.apply(ret, repeator.group(store, repeat, item.children));
        break;
      }
    }
  })
  return ret;
}

// ---- stringify ----
function stringify(list) {
  let ret = '';
  list.forEach(({ template, values }) => {
    let i = 0;
    ret += template.replace(/\${(.+?)}/g, () => {
      const value = values[i];
      i += 1;
      switch (value.type) {
        case Type.int: case Type.set: case Type.alias: {
          return value.value;
        }
        case Type.graph: {
          return value.value.map((edge) => `${edge[0]} ${edge[1]}`).join('\n');
        }
      }
    });
    ret += '\n';
  });
  return ret;
}

// ---- init ----
let initValue = '';
initValue += 'constraint n int 5 10\n';
initValue += 'constraint a int 1 n\n';
initValue += 'constraint b int 5 1000\n';
initValue += 'constraint c int 5 10000\n';
initValue += 'repeat 1 ${n}\n';
initValue += 'repeat n ${a} ${b} ${c}\n';
document.querySelector('#input').value = initValue;
function handleClick() {
  const ret = parse(document.querySelector('#input').value);
  try {
    document.querySelector('#output').value = stringify(generate(...ret));
  } catch (error) {
    window.alert(error);
  }
}
document.querySelector('#click').addEventListener('click', handleClick);

})();
</script>
